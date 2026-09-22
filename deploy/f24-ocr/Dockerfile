# syntax=docker/dockerfile:1
FROM python:3.12-slim
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr tesseract-ocr-ita tesseract-ocr-eng poppler-utils \
    && rm -rf /var/lib/apt/lists/*
RUN pip install --no-cache-dir \
    fastapi "uvicorn[standard]" python-multipart pytesseract pdf2image pillow "pypdf>=5,<7"

# Embedded from app/Http/Controllers/Api/Private/Invoice/image_pro.py.
# Keep this standalone Dockerfile in sync with that source.
RUN cat > /app/main.py <<'PY'
"""F24 OCR API: one independent invoice PDF per page, including repeated CFs."""
import base64
from datetime import date
from io import BytesIO
import json
import logging
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import tempfile

import pytesseract
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from pdf2image import convert_from_path
from pypdf import PdfReader, PdfWriter

app = FastAPI(title="F24 Codice Fiscale Reader", version="3.2.0")
logger = logging.getLogger("uvicorn.error")
API_KEY = os.getenv("API_KEY", "cf21978a406f3dd83f265498a48bd8113dc5da235c18e37db55ae3dec254649d")
MAX_FILE_BYTES = 10 * 1024 * 1024

# Personal CF (including omocodia) or the 11-digit company CF.
CF_PARTS = [r"[A-Z]"] * 6 + [r"[0-9LMNPQRSTUV]"] * 2 + [r"[ABCDEHLMPRST]"] + [r"[0-9LMNPQRSTUV]"] * 2 + [r"[A-Z]"] + [r"[0-9LMNPQRSTUV]"] * 3 + [r"[A-Z]"]
CF_REGEX = re.compile(
    r"CODICE\s+FISCALE\s*[:.-]?\s*"
    r"(?P<cf>" + r"\s*".join(CF_PARTS) + r"(?![A-Z0-9])|\d(?:[ \t]*\d){10}(?![A-Z0-9]|[ \t]+\d\b))"
)


def extract_cf(text: str) -> str | None:
    # Preserve the words after FISCALE: the co-obligor field must not match.
    text = re.sub(r"[^A-Z0-9\s:.-]", " ", text.upper())
    match = CF_REGEX.search(text)
    return re.sub(r"\s+", "", match.group("cf")) if match else None


def extract_reference(text: str) -> dict:
    # The user-defined due date and invoice number come from the footer only,
    # not tax-period dates, page numbers, or the upload filename.
    references = set()
    for match in re.finditer(
        r"\bRIFERIMENTO\s*:\s*(\d{1,2})\s*/\s*(\d{1,2})\s*/\s*(\d{4})"
        r"(?!\d)(?:[ \t]*/[ \t]*(\d+)(?!\w))?", text, re.IGNORECASE,
    ):
        day, month, year, number = match.groups()
        try:
            due_date = date(int(year), int(month), int(day)).isoformat()
        except ValueError:
            due_date = None
        references.add((due_date, number))
    due_date, number = next(iter(references)) if len(references) == 1 else (None, None)
    return {"due_date": due_date, "invoice_number": number}


def read_page_text(pdf_path: str, page_number: int) -> str:
    # Render only the current page, as in the original container implementation.
    if os.getenv("TESSERACT_CMD"):
        pytesseract.pytesseract.tesseract_cmd = os.environ["TESSERACT_CMD"]
    pages = convert_from_path(
        pdf_path, dpi=300, first_page=page_number, last_page=page_number,
        thread_count=1, timeout=60, poppler_path=os.getenv("POPPLER_PATH"),
    )
    try:
        language = os.getenv("OCR_LANG", "ita+eng")
        text = pytesseract.image_to_string(pages[0], lang=language, timeout=60)
        if not all(extract_reference(text).values()):
            # The reference is small and can be missed during whole-page OCR.
            # Reuse the rendered image for a focused footer pass.
            page = pages[0]
            try:
                with page.crop((0, int(page.height * 0.88), page.width, page.height)) as footer:
                    footer_text = pytesseract.image_to_string(footer, lang=language, config="--psm 11", timeout=60)
                    text += "\n" + footer_text
            except Exception:
                logger.warning("F24 footer OCR unavailable on page %s", page_number)
        return text
    finally:
        for page in pages:
            page.close()


def read_text_pages(pdf_path: str, page_count: int) -> list[str]:
    # Poppler reconstructs the visual reading order of F24 template/overlay
    # PDFs. Plain PDF extraction can put the CF far away from its field label.
    # pdftotext is already installed by poppler-utils in the container.
    executable = "pdftotext.exe" if os.name == "nt" else "pdftotext"
    if os.getenv("POPPLER_PATH"):
        executable = str(Path(os.environ["POPPLER_PATH"]) / executable)
    try:
        result = subprocess.run(
            [executable, "-layout", "-enc", "UTF-8", pdf_path, "-"],
            capture_output=True, check=True, timeout=30,
        )
        pages = result.stdout.decode("utf-8").split("\f")
        if pages and not pages[-1].strip():
            pages.pop()
        # Never shift identifiers between pages when extraction is incomplete.
        return pages if len(pages) == page_count else [""] * page_count
    except (OSError, subprocess.SubprocessError, UnicodeError):
        logger.warning("F24 text extraction unavailable; using page OCR")
        return [""] * page_count


def process_pdf(pdf_path: str, filename: str) -> dict:
    result = {"file": filename, "success": False, "status": "error", "invoices": []}
    try:
        with open(pdf_path, "rb") as source:
            reader = PdfReader(source)
            if reader.is_encrypted and not reader.decrypt(""):
                raise ValueError("Password-protected PDF")
            page_count = len(reader.pages)
            if not page_count:
                raise ValueError("Empty PDF")
            text_pages = read_text_pages(pdf_path, page_count)
            invoices = []
            for page_number in range(1, page_count + 1):
                invoice = {"page": page_number, "success": False, "cf": None, "status": "error",
                           "due_date": None, "invoice_number": None}
                try:
                    text = text_pages[page_number - 1]
                    cf = extract_cf(text)
                    reference = extract_reference(text)
                    if not cf or not all(reference.values()):
                        try:
                            ocr_text = read_page_text(pdf_path, page_number)
                            cf = cf or extract_cf(ocr_text)
                            ocr_reference = extract_reference(ocr_text)
                            # Supplement partial references only when known values agree.
                            if all(not value or value == ocr_reference[key] for key, value in reference.items()):
                                reference = {key: value or ocr_reference[key] for key, value in reference.items()}
                        except Exception:
                            if not cf:
                                raise
                            logger.warning("F24 reference OCR failed on page %s", page_number)
                    invoice.update(reference)
                    if not all(reference.values()):
                        invoice["metadata_warning"] = "Invoice number or due date could not be read from Riferimento"
                    invoice.update(success=True, cf=cf, status="processed" if cf else "not_found")
                    if cf:
                        # Copy the original page, preserving its appearance and
                        # resolution. Never deduplicate or collect pages by CF.
                        writer = PdfWriter()
                        writer.add_page(reader.pages[page_number - 1])
                        output = BytesIO()
                        writer.write(output)
                        invoice["pdf_base64"] = base64.b64encode(output.getvalue()).decode("ascii")
                    else:
                        invoice["message"] = "Codice Fiscale not found on this page"
                except Exception:
                    logger.exception("F24 processing failed on page %s", page_number)
                    invoice.update(success=False, status="error", error="Unable to process PDF page")
                invoices.append(invoice)
            result.update(success=True, status="processed", page_count=page_count, invoices=invoices)
            return result
    except Exception:
        logger.exception("F24 PDF processing failed")
        return {**result, "error": "Unable to process PDF"}


def process_file(upload: UploadFile) -> dict:
    result = {"file": upload.filename, "success": False, "status": "error", "invoices": []}
    if upload.content_type != "application/pdf":
        return {**result, "error": "Only PDF files are accepted"}
    try:
        with tempfile.TemporaryDirectory(prefix="f24-") as directory:
            pdf_path = os.path.join(directory, "input.pdf")
            size = 0
            with open(pdf_path, "wb") as output:
                while chunk := upload.file.read(1024 * 1024):
                    size += len(chunk)
                    if size > MAX_FILE_BYTES:
                        return {**result, "error": "File exceeds 10 MB"}
                    output.write(chunk)
            if size == 0:
                return {**result, "error": "Empty PDF file"}
            return process_pdf(pdf_path, upload.filename or "invoice.pdf")
    except Exception:
        logger.exception("F24 upload processing failed")
        return {**result, "error": "Unable to process PDF"}


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/read-cf")
def read_cf(
    files: list[UploadFile] = File(..., alias="files[]"),
    x_api_key: str | None = Header(default=None),
):
    if not API_KEY:
        raise HTTPException(status_code=503, detail="API_KEY is not configured")
    if not secrets.compare_digest((x_api_key or "").encode(), API_KEY.encode()):
        raise HTTPException(status_code=401, detail="Unauthorized")
    # Preserve upload order; a bad file/page never stops the rest of the batch.
    # Keep this handler synchronous so OCR stays off the ASGI event loop.
    return [process_file(upload) for upload in files]


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({"success": False, "error": "Usage: image_pro.py <pdf_path>"}))
        sys.exit(1)
    result = process_pdf(sys.argv[1], Path(sys.argv[1]).name)
    print(json.dumps(result))
    sys.exit(0 if result["success"] else 1)
PY
EXPOSE 8000
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
