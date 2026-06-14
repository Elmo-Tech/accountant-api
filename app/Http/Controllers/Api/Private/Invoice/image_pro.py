import re
import pytesseract
import sys
import json
from pdf2image import convert_from_path
from PIL import Image

# Configure paths
poppler_path = r'C:\Program Files\poppler-24.08.0\Library\bin'
pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'

# Regex pattern to match spaced Codice Fiscale
CF_REGEX = r"CODICE\s+FISCALE\s+([A-Z] [A-Z] [A-Z] [A-Z] [A-Z] [A-Z] [0-9] [0-9] [A-Z] [0-9] [0-9] [A-Z] [0-9] [0-9] [0-9] [A-Z])\b"

def extract_codice_fiscale_from_pdf(pdf_path):
    try:
        pages = convert_from_path(pdf_path, dpi=300, poppler_path=poppler_path)
    except Exception as e:
        print(json.dumps({"error": f"PDF processing error: {str(e)}"}))
        return

    full_text = ""

    for page in pages:
        try:
            text = pytesseract.image_to_string(page)
            full_text += text + "\n"
        except Exception as e:
            print(json.dumps({"error": f"OCR error: {str(e)}"}))
            return

    # Normalize the OCR text
    full_text = full_text.upper()
    full_text = re.sub(r'[^A-Z0-9\s]', '', full_text)
    full_text = re.sub(r'\s+', ' ', full_text)

    match = re.search(CF_REGEX, full_text)

    if match:
        cf_spaced = match.group(1)
        cf_clean = cf_spaced.replace(' ', '')
        print(json.dumps({"cf": cf_clean}))
    else:
        print(json.dumps({"cf": None, "message": "Codice Fiscale not found"}))

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({"error": "Usage: script.py <pdf_path>"}))
        sys.exit(1)

    pdf_path = sys.argv[1]
    extract_codice_fiscale_from_pdf(pdf_path)
    sys.exit(0)
