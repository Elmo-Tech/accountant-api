"""F24 OCR API: one independent invoice PDF per page, including repeated CFs."""
import base64
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

app = FastAPI(title="F24 Codice Fiscale Reader", version="3.1.0")
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


def read_page_cf(pdf_path: str, page_number: int) -> str | None:
    # Render only the current page, as in the original container implementation.
    if os.getenv("TESSERACT_CMD"):
        pytesseract.pytesseract.tesseract_cmd = os.environ["TESSERACT_CMD"]
    pages = convert_from_path(
        pdf_path, dpi=300, first_page=page_number, last_page=page_number,
        thread_count=1, timeout=60, poppler_path=os.getenv("POPPLER_PATH"),
    )
    try:
        text = pytesseract.image_to_string(pages[0], lang=os.getenv("OCR_LANG", "ita+eng"), timeout=60)
        return extract_cf(text)
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
                invoice = {"page": page_number, "success": False, "cf": None, "status": "error"}
                try:
                    cf = extract_cf(text_pages[page_number - 1])
                    if not cf:
                        cf = read_page_cf(pdf_path, page_number)
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
