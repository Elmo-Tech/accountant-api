# syntax=docker/dockerfile:1

FROM python:3.12-slim

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        tesseract-ocr \
        tesseract-ocr-ita \
        tesseract-ocr-eng \
        poppler-utils \
    && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir \
    fastapi \
    "uvicorn[standard]" \
    python-multipart \
    pytesseract \
    pdf2image \
    pillow \
    "pypdf>=5,<7"

RUN cat > /app/main.py <<'PY'
import base64
from datetime import date
from io import BytesIO
import logging
import os
from pathlib import Path
import re
import secrets
import subprocess
import tempfile

import pytesseract
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from pdf2image import convert_from_path
from pypdf import PdfReader, PdfWriter


app = FastAPI(
    title="F24 Codice Fiscale Reader",
    version="3.2.0",
)

logger = logging.getLogger("uvicorn.error")

API_KEY = os.getenv("API_KEY", "cf21978a406f3dd83f265498a48bd8113dc5da235c18e37db55ae3dec254649d")
OCR_LANG = os.getenv("OCR_LANG", "ita+eng")
MAX_FILE_BYTES = 10 * 1024 * 1024


# Italian personal Codice Fiscale, including omocodia characters.
CF_PARTS = (
    [r"[A-Z]"] * 6
    + [r"[0-9LMNPQRSTUV]"] * 2
    + [r"[ABCDEHLMPRST]"]
    + [r"[0-9LMNPQRSTUV]"] * 2
    + [r"[A-Z]"]
    + [r"[0-9LMNPQRSTUV]"] * 3
    + [r"[A-Z]"]
)

CF_REGEX = re.compile(
    r"CODICE\s+FISCALE\s*[:.\-]?\s*"
    r"(?P<cf>"
    + r"\s*".join(CF_PARTS)
    + r"(?![A-Z0-9])"
    + r"|"
    + r"\d(?:[ \t]*\d){10}(?![A-Z0-9]|[ \t]+\d\b)"
    + r")",
    re.IGNORECASE,
)

REFERENCE_REGEX = re.compile(
    r"\bRIFERIMENTO\s*:\s*"
    r"(\d{1,2})\s*/\s*(\d{1,2})\s*/\s*(\d{4})"
    r"(?!\d)"
    r"(?:[ \t]*/[ \t]*(\d+)(?!\w))?",
    re.IGNORECASE,
)


def extract_cf(text: str) -> str | None:
    if not text:
        return None

    cleaned = re.sub(
        r"[^A-Z0-9\s:.\-]",
        " ",
        text.upper(),
    )

    match = CF_REGEX.search(cleaned)

    if not match:
        return None

    return re.sub(
        r"\s+",
        "",
        match.group("cf"),
    )


def extract_reference(text: str) -> dict:
    if not text:
        return {
            "due_date": None,
            "invoice_number": None,
        }

    references = set()

    for match in REFERENCE_REGEX.finditer(text):
        day, month, year, number = match.groups()

        try:
            due_date = date(
                int(year),
                int(month),
                int(day),
            ).isoformat()
        except ValueError:
            due_date = None

        references.add(
            (
                due_date,
                number,
            )
        )

    if len(references) != 1:
        return {
            "due_date": None,
            "invoice_number": None,
        }

    due_date, invoice_number = next(
        iter(references)
    )

    return {
        "due_date": due_date,
        "invoice_number": invoice_number,
    }


def read_pdf_text_pages(
    pdf_path: str,
    page_count: int,
) -> list[str]:
    executable = "pdftotext"

    if os.getenv("POPPLER_PATH"):
        executable = str(
            Path(
                os.environ["POPPLER_PATH"]
            ) / "pdftotext"
        )

    try:
        result = subprocess.run(
            [
                executable,
                "-layout",
                "-enc",
                "UTF-8",
                pdf_path,
                "-",
            ],
            capture_output=True,
            check=True,
            timeout=30,
        )

        pages = (
            result.stdout
            .decode("utf-8")
            .split("\f")
        )

        if pages and not pages[-1].strip():
            pages.pop()

        if len(pages) != page_count:
            return [""] * page_count

        return pages

    except (
        OSError,
        subprocess.SubprocessError,
        UnicodeError,
    ):
        logger.warning(
            "PDF text extraction failed; OCR fallback will be used"
        )

        return [""] * page_count


def read_page_ocr_text(
    pdf_path: str,
    page_number: int,
) -> str:
    if os.getenv("TESSERACT_CMD"):
        pytesseract.pytesseract.tesseract_cmd = (
            os.environ["TESSERACT_CMD"]
        )

    images = convert_from_path(
        pdf_path,
        dpi=300,
        first_page=page_number,
        last_page=page_number,
        thread_count=1,
        timeout=60,
        poppler_path=os.getenv("POPPLER_PATH"),
    )

    if not images:
        return ""

    try:
        page_image = images[0]

        text = pytesseract.image_to_string(
            page_image,
            lang=OCR_LANG,
            timeout=60,
        )

        reference = extract_reference(text)

        if not all(reference.values()):
            try:
                footer_top = int(
                    page_image.height * 0.85
                )

                with page_image.crop(
                    (
                        0,
                        footer_top,
                        page_image.width,
                        page_image.height,
                    )
                ) as footer_image:
                    footer_text = (
                        pytesseract.image_to_string(
                            footer_image,
                            lang=OCR_LANG,
                            config="--psm 11",
                            timeout=60,
                        )
                    )

                text += "\n" + footer_text

            except Exception:
                logger.warning(
                    "Footer OCR failed on page %s",
                    page_number,
                )

        return text

    finally:
        for image in images:
            image.close()


def build_page_pdf_base64(
    reader: PdfReader,
    page_index: int,
) -> str:
    writer = PdfWriter()

    writer.add_page(
        reader.pages[page_index]
    )

    output = BytesIO()

    writer.write(output)

    return base64.b64encode(
        output.getvalue()
    ).decode("ascii")


def process_pdf(
    pdf_path: str,
    filename: str,
) -> dict:
    result = {
        "file": filename,
        "success": False,
        "status": "error",
        "invoices": [],
    }

    try:
        with open(pdf_path, "rb") as source:
            reader = PdfReader(source)

            if reader.is_encrypted:
                decrypted = reader.decrypt("")

                if not decrypted:
                    raise ValueError(
                        "Password-protected PDF"
                    )

            page_count = len(reader.pages)

            if page_count == 0:
                raise ValueError(
                    "Empty PDF"
                )

            text_pages = read_pdf_text_pages(
                pdf_path,
                page_count,
            )

            invoices = []

            for page_index in range(page_count):
                page_number = page_index + 1

                invoice = {
                    "page": page_number,
                    "success": False,
                    "cf": None,
                    "status": "error",
                    "due_date": None,
                    "invoice_number": None,
                }

                try:
                    text = text_pages[
                        page_index
                    ]

                    cf = extract_cf(text)

                    reference = extract_reference(
                        text
                    )

                    if (
                        not cf
                        or not all(
                            reference.values()
                        )
                    ):
                        ocr_text = (
                            read_page_ocr_text(
                                pdf_path,
                                page_number,
                            )
                        )

                        if not cf:
                            cf = extract_cf(
                                ocr_text
                            )

                        ocr_reference = (
                            extract_reference(
                                ocr_text
                            )
                        )

                        for key in (
                            "due_date",
                            "invoice_number",
                        ):
                            if (
                                not reference[key]
                                and ocr_reference[key]
                            ):
                                reference[key] = (
                                    ocr_reference[key]
                                )

                    invoice.update(
                        reference
                    )

                    if cf:
                        invoice.update(
                            {
                                "success": True,
                                "cf": cf,
                                "status": "processed",
                                "pdf_base64": (
                                    build_page_pdf_base64(
                                        reader,
                                        page_index,
                                    )
                                ),
                            }
                        )

                        if not all(
                            reference.values()
                        ):
                            invoice[
                                "metadata_warning"
                            ] = (
                                "Invoice number or due date "
                                "could not be read from "
                                "Riferimento"
                            )

                    else:
                        invoice.update(
                            {
                                "success": True,
                                "cf": None,
                                "status": "not_found",
                                "message": (
                                    "Codice Fiscale not found "
                                    "on this page"
                                ),
                            }
                        )

                except Exception:
                    logger.exception(
                        "F24 processing failed on page %s",
                        page_number,
                    )

                    invoice.update(
                        {
                            "success": False,
                            "status": "error",
                            "error": (
                                "Unable to process PDF page"
                            ),
                        }
                    )

                invoices.append(
                    invoice
                )

            result.update(
                {
                    "success": True,
                    "status": "processed",
                    "page_count": page_count,
                    "invoices": invoices,
                }
            )

            return result

    except Exception:
        logger.exception(
            "F24 PDF processing failed"
        )

        return {
            **result,
            "error": "Unable to process PDF",
        }


def process_file(
    upload: UploadFile,
) -> dict:
    result = {
        "file": upload.filename,
        "success": False,
        "status": "error",
        "invoices": [],
    }

    if upload.content_type != "application/pdf":
        return {
            **result,
            "error": "Only PDF files are accepted",
        }

    try:
        with tempfile.TemporaryDirectory(
            prefix="f24-"
        ) as directory:
            pdf_path = os.path.join(
                directory,
                "input.pdf",
            )

            size = 0

            with open(
                pdf_path,
                "wb",
            ) as output:
                while True:
                    chunk = upload.file.read(
                        1024 * 1024
                    )

                    if not chunk:
                        break

                    size += len(chunk)

                    if size > MAX_FILE_BYTES:
                        return {
                            **result,
                            "error": (
                                "File exceeds 10 MB"
                            ),
                        }

                    output.write(
                        chunk
                    )

            if size == 0:
                return {
                    **result,
                    "error": "Empty PDF file",
                }

            return process_pdf(
                pdf_path,
                upload.filename
                or "invoice.pdf",
            )

    except Exception:
        logger.exception(
            "F24 upload processing failed"
        )

        return {
            **result,
            "error": "Unable to process PDF",
        }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "version": "3.2.0",
    }


@app.post("/read-cf")
def read_cf(
    files: list[UploadFile] = File(
        ...,
        alias="files[]",
    ),
    x_api_key: str | None = Header(
        default=None
    ),
):
    if not API_KEY:
        raise HTTPException(
            status_code=503,
            detail=(
                "API_KEY is not configured"
            ),
        )

    if not secrets.compare_digest(
        (x_api_key or "").encode(),
        API_KEY.encode(),
    ):
        raise HTTPException(
            status_code=401,
            detail="Unauthorized",
        )

    return [
        process_file(upload)
        for upload in files
    ]
PY

EXPOSE 8000

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
