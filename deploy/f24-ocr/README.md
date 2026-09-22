# F24 per-invoice delivery

This is the supplied FastAPI/Uvicorn container updated to return **one PDF per
F24 page**, including repeated occurrences of the same codice fiscale. The
sample format contains one complete F24 per page; multi-page invoices or several
invoices on a single page require a different boundary rule.

## Deploy

`Dockerfile` is self-contained, just like the original. Replace the Dockerfile
in the existing container deployment with this file, rebuild and redeploy that
service. Keep port 8000, the existing HTTPS domain/reverse proxy, and the API key.
The original key remains the fallback; the `API_KEY` environment variable can
override it. The PHP controller and container must use the same key.

For a local Docker build from the repository root:

```sh
docker build -t f24-ocr:3.2 deploy/f24-ocr
```

Deploy the changed `SendInvoiceController.php` together with the rebuilt
container. An old successful single-CF response is rejected with HTTP 502 to
prevent attaching a complete batch to a single client.

The PHP OCR request timeout is 600 seconds. Configure the PHP/web server and
reverse proxy to allow the same processing window for large scanned batches.

## Behavior and response

`GET /health`, `POST /read-cf`, multipart `files[]`, `X-API-Key`, the synchronous
OCR handler, Italian/English OCR, temporary-directory cleanup, and 10 MB per-file
limit are preserved. `pypdf` copies each original page without rasterizing its
attachment. The source is also available in
`app/Http/Controllers/Api/Private/Invoice/image_pro.py`; a test verifies that the
Dockerfile's embedded Python is identical.

Version 3.1 reads the text layer first using Poppler's `pdftotext -layout`
(already included in the image). Only pages without a recognized taxpayer CF
use OCR. This avoids running 26 OCR operations for a 26-page text PDF. A failed
text extraction or a page-count mismatch falls back to OCR without moving CFs
between pages. `/openapi.json` reports version `3.2.0` after deployment.

Version 3.2 also extracts the footer `Riferimento:30/09/2026/77` as
`due_date: "2026-09-30"` and `invoice_number: "77"`. As requested, the date in
this reference is used as the due date. Only that labelled reference is used;
tax periods, page numbers, and filenames are not substitutes. Invalid calendar
dates and ambiguous references remain unavailable. OCR is attempted when the
text layer lacks the CF, invoice number, or date. Each page is rendered once;
a focused footer OCR pass is attempted if whole-page OCR misses the reference.
Missing metadata is returned as null with `metadata_warning`; a readable CF
and its PDF can still be sent. Update both the container and controller for
this feature.

The response remains an ordered array with one result per upload. Successful
files contain `page_count` and an ordered `invoices` array, with one record per
page: `page` (1-based), `cf`, `success`, `status`, and `pdf_base64` for recognized
pages. Python does not merge or deduplicate the PDFs by CF. Missing CFs, page OCR failures,
and invalid files are reported independently.

Laravel checks each CF against clients, groups the valid invoices by normalized
CF across all files in the request, and sends **one email per client** with a
separate PDF attachment for every invoice. Different clients remain in separate
emails even though all emails go to the test address `mr10dev10@gmail.com`. Client
email addresses are not used. Unknown clients and failed pages are skipped;
valid invoices still send. A mail failure is reported on every invoice in that
client's email without blocking other clients. API `results` include source filename, zero-based `file_index`,
page number, and sending outcome, without PDF/base64 data. The original batch
is never used as an attachment.

The Dockerfile is the user-supplied version, with its embedded Python mirrored
in `image_pro.py`. The email uses only the first attached invoice's date for
that client, in upload/page order (not the earliest date). All PDFs remain
attached separately. The message is:

```text
Gentile Cliente,

in allegato il modello F24 in scadenza il 30/09/2026.
```

The subject is `Modelli F24 in scadenza - 30/09/2026` and the sender display name
is `Servizio F24`, using the existing configured sender email address. This
override applies only to F24 emails. If the first invoice's date is missing,
the body says `La data di scadenza non è disponibile.` and the subject is
`Invio modelli F24`; later dates are not substituted. The API still exposes
`invoice_number` and ISO `due_date` for each invoice. The sole test recipient
remains `mr10dev10@gmail.com`.

Email grouping is implemented in `SendInvoiceController.php`; changing this
grouping does not require rebuilding the Python container.

## Tests

Install the Python dependencies from the Dockerfile plus `httpx` for TestClient,
then run:

```sh
python -m unittest discover -s tests/python -p "test_*.py"
php vendor/bin/phpunit tests/Feature/SendUploadedInvoiceTest.php
```

Python tests use FastAPI's actual multipart endpoint and mock only OCR; PHP tests
fake HTTP/mail and use an in-memory database. Neither sends real email.
Optional `POPPLER_PATH`, `TESSERACT_CMD`, and `OCR_LANG` support local OCR testing;
the container defaults remain system executables and `ita+eng`.
