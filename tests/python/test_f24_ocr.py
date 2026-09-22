import base64
from io import BytesIO
import importlib.util
import subprocess
from types import SimpleNamespace
from pathlib import Path
import unittest
from unittest.mock import patch

from fastapi.testclient import TestClient
from PIL import Image
from pypdf import PdfReader, PdfWriter
from pypdf.generic import DictionaryObject, NameObject, DecodedStreamObject

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / 'app/Http/Controllers/Api/Private/Invoice/image_pro.py'
spec = importlib.util.spec_from_file_location('f24_ocr', SOURCE)
ocr = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ocr)


def pdf_pages(texts):
    writer = PdfWriter()
    for text in texts:
        page = writer.add_blank_page(width=595, height=842)
        page[NameObject('/Resources')] = DictionaryObject({
            NameObject('/Font'): DictionaryObject({NameObject('/F1'): DictionaryObject({
                NameObject('/Type'): NameObject('/Font'),
                NameObject('/Subtype'): NameObject('/Type1'),
                NameObject('/BaseFont'): NameObject('/Helvetica'),
            })})
        })
        stream = DecodedStreamObject()
        stream.set_data(('BT /F1 12 Tf 20 800 Td (' + text + ') Tj ET').encode('ascii'))
        page[NameObject('/Contents')] = stream
    output = BytesIO()
    writer.write(output)
    return output.getvalue()


class F24OcrTest(unittest.TestCase):
    def setUp(self):
        self.client = TestClient(ocr.app)
        self.key_patch = patch.object(ocr, 'API_KEY', 'test-only-key')
        self.key_patch.start()
        self.addCleanup(self.key_patch.stop)
        # Existing OCR tests exercise the scanned-document fallback without
        # requiring a local Poppler installation.
        self.text_patch = patch.object(ocr, 'read_text_pages', side_effect=lambda path, count: [''] * count)
        self.text_patch.start()
        self.addCleanup(self.text_patch.stop)

    def upload(self, files):
        return self.client.post('/read-cf', headers={'X-API-Key': 'test-only-key'}, files=files)

    def test_repeated_client_and_other_client_get_independent_original_pages(self):
        texts = ['invoice A', 'invoice B', 'invoice C', 'invoice D']
        codes = ['LHDMMD97T01Z336N', 'RSSMRA80A01H501U', 'LHDMMD97T01Z336N', 'LHDMMD97T01Z336N']
        with patch.object(ocr, 'read_page_text', side_effect=['CODICE FISCALE ' + code for code in codes]) as read:
            response = self.upload([('files[]', ('batch.pdf', pdf_pages(texts), 'application/pdf'))])
        self.assertEqual(response.status_code, 200)
        result = response.json()[0]
        self.assertEqual(result['page_count'], 4)
        self.assertEqual([i['cf'] for i in result['invoices']], codes)
        self.assertEqual([call.args[1] for call in read.call_args_list], [1, 2, 3, 4])
        for index, invoice in enumerate(result['invoices']):
            split = PdfReader(BytesIO(base64.b64decode(invoice['pdf_base64'])))
            self.assertEqual(len(split.pages), 1)
            self.assertEqual(invoice['page'], index + 1)
            self.assertEqual(split.pages[0].extract_text(), texts[index])

    def test_missing_cf_and_ocr_errors_do_not_inherit_previous_cf_or_stop_batch(self):
        with patch.object(ocr, 'read_page_text', side_effect=['CODICE FISCALE LHDMMD97T01Z336N', '', RuntimeError('OCR timeout'), 'CODICE FISCALE RSSMRA80A01H501U']):
            with self.assertLogs('uvicorn.error', level='ERROR'):
                result = self.upload([('files[]', ('batch.pdf', pdf_pages(['a', 'b', 'c', 'd']), 'application/pdf'))]).json()[0]
        self.assertEqual(result['invoices'][1]['status'], 'not_found')
        self.assertIsNone(result['invoices'][1]['cf'])
        self.assertNotIn('pdf_base64', result['invoices'][1])
        self.assertFalse(result['invoices'][2]['success'])
        self.assertTrue(result['invoices'][3]['success'])

    def test_bad_file_does_not_stop_next_upload_with_same_name(self):
        with patch.object(ocr, 'read_page_text', return_value='CODICE FISCALE LHDMMD97T01Z336N'):
            result = self.upload([
                ('files[]', ('batch.pdf', b'', 'application/pdf')),
                ('files[]', ('batch.pdf', pdf_pages(['second']), 'application/pdf')),
            ]).json()
        self.assertFalse(result[0]['success'])
        self.assertTrue(result[1]['success'])
        self.assertEqual(result[1]['page_count'], 1)

    def test_health_auth_mime_and_file_size_validation(self):
        self.assertEqual(self.client.get('/health').json(), {'status': 'ok'})
        file = [('files[]', ('test.pdf', b'bad pdf', 'application/pdf'))]
        self.assertEqual(self.client.post('/read-cf', files=file).status_code, 401)
        with patch.object(ocr, 'API_KEY', ''):
            self.assertEqual(self.upload(file).status_code, 503)
        result = self.upload([('files[]', ('test.txt', b'text', 'text/plain'))]).json()[0]
        self.assertFalse(result['success'])
        with patch.object(ocr, 'MAX_FILE_BYTES', 3):
            self.assertEqual(self.upload(file).json()[0]['error'], 'File exceeds 10 MB')

    def test_cf_formats_and_co_obligor_are_handled(self):
        self.assertEqual(ocr.extract_cf('CODICE FISCALE L H D M M D 9 7 T 0 1 Z 3 3 6 N'), 'LHDMMD97T01Z336N')
        self.assertEqual(ocr.extract_cf('CODICE FISCALE: 00987920196'), '00987920196')
        self.assertEqual(ocr.extract_cf('CODICE FISCALE\nRSSMRA80A01H501U\n0 1 2 3'), 'RSSMRA80A01H501U')
        self.assertIsNone(ocr.extract_cf('CODICE FISCALE del coobbligato RSSMRA80A01H501U'))
        self.assertIsNone(ocr.extract_cf('CODICE FISCALE 123456789012'))
        self.assertIsNone(ocr.extract_cf('CODICE FISCALE 1 2 3 4 5 6 7 8 9 0 1 2'))

    def test_standalone_dockerfile_contains_exact_service_source(self):
        dockerfile = (ROOT / 'deploy/f24-ocr/Dockerfile').read_text(encoding='utf-8')
        embedded = dockerfile.split("RUN cat > /app/main.py <<'PY'\n", 1)[1].split('\nPY\n', 1)[0]
        self.assertEqual(embedded, SOURCE.read_text(encoding='utf-8').rstrip('\n'))

    def test_text_batch_does_not_run_ocr_and_preserves_page_client_order(self):
        self.text_patch.stop()
        text = 'CODICE FISCALE LHDMMD97T01Z336N\nRiferimento:30/09/2026/77\fCODICE FISCALE RSSMRA80A01H501U\nRiferimento:01/10/2026/78\fCODICE FISCALE LHDMMD97T01Z336N\nRiferimento:02/10/2026/79\f'
        with patch.object(ocr.subprocess, 'run', return_value=SimpleNamespace(stdout=text.encode())):
            with patch.object(ocr, 'read_page_text', side_effect=AssertionError('Unexpected OCR')):
                result = self.upload([('files[]', ('batch.pdf', pdf_pages(['a', 'b', 'c']), 'application/pdf'))]).json()[0]
        self.assertEqual([i['cf'] for i in result['invoices']], ['LHDMMD97T01Z336N', 'RSSMRA80A01H501U', 'LHDMMD97T01Z336N'])
        self.assertTrue(all(i['success'] and 'pdf_base64' in i for i in result['invoices']))
        self.assertEqual([i['invoice_number'] for i in result['invoices']], ['77', '78', '79'])
        self.assertEqual([i['due_date'] for i in result['invoices']], ['2026-09-30', '2026-10-01', '2026-10-02'])

    def test_reference_parser_uses_footer_and_validates_dates(self):
        self.assertEqual(ocr.extract_reference('periodo di riferimento: 2024\nRiferimento:30/09/2026/77'),
                         {'due_date': '2026-09-30', 'invoice_number': '77'})
        self.assertEqual(ocr.extract_reference('riferimento : 1 / 2 / 2028 / 007'),
                         {'due_date': '2028-02-01', 'invoice_number': '007'})
        self.assertIsNone(ocr.extract_reference('Riferimento:31/02/2026/77')['due_date'])
        self.assertEqual(ocr.extract_reference('Riferimento:29/02/2028/77')['due_date'], '2028-02-29')
        self.assertEqual(ocr.extract_reference('Data 30/09/2026 numero 77'), {'due_date': None, 'invoice_number': None})
        self.assertIsNone(ocr.extract_reference('Riferimento:30/09/2026')['invoice_number'])
        self.assertEqual(ocr.extract_reference('Riferimento:30/09/2026/77\nRiferimento:01/10/2026/78'),
                         {'due_date': None, 'invoice_number': None})

    def test_scanned_page_extracts_reference_and_cf_from_same_ocr_pass(self):
        with patch.object(ocr, 'read_page_text', return_value='CODICE FISCALE LHDMMD97T01Z336N\nRiferimento:30/09/2026/77') as read:
            invoice = self.upload([('files[]', ('batch.pdf', pdf_pages(['scanned']), 'application/pdf'))]).json()[0]['invoices'][0]
        self.assertEqual(invoice['invoice_number'], '77')
        self.assertEqual(invoice['due_date'], '2026-09-30')
        read.assert_called_once()

    def test_footer_ocr_recovers_reference_without_rendering_page_again(self):
        page = Image.new('RGB', (600, 840), 'white')
        with patch.object(ocr, 'convert_from_path', return_value=[page]) as render:
            with patch.object(ocr.pytesseract, 'image_to_string', side_effect=[
                'CODICE FISCALE LHDMMD97T01Z336N', 'Riferimento:30/09/2026/77',
            ]) as read:
                text = ocr.read_page_text('batch.pdf', 1)
        render.assert_called_once()
        self.assertEqual(read.call_count, 2)
        self.assertEqual(ocr.extract_reference(text), {'due_date': '2026-09-30', 'invoice_number': '77'})

    def test_missing_reference_does_not_drop_recognized_invoice_or_inherit_previous_date(self):
        self.text_patch.stop()
        text = 'CODICE FISCALE LHDMMD97T01Z336N\nRiferimento:30/09/2026/77\fCODICE FISCALE LHDMMD97T01Z336N\f'
        with patch.object(ocr.subprocess, 'run', return_value=SimpleNamespace(stdout=text.encode())):
            with patch.object(ocr, 'read_page_text', return_value=''):
                invoices = self.upload([('files[]', ('batch.pdf', pdf_pages(['a', 'b']), 'application/pdf'))]).json()[0]['invoices']
        self.assertTrue(invoices[1]['success'])
        self.assertIn('pdf_base64', invoices[1])
        self.assertIsNone(invoices[1]['due_date'])
        self.assertIsNone(invoices[1]['invoice_number'])
        self.assertIn('metadata_warning', invoices[1])

    def test_incomplete_text_output_never_assigns_cf_to_wrong_page(self):
        self.text_patch.stop()
        with patch.object(ocr.subprocess, 'run', return_value=SimpleNamespace(stdout=b'CODICE FISCALE LHDMMD97T01Z336N\f')):
            self.assertEqual(ocr.read_text_pages('batch.pdf', 2), ['', ''])

    def test_text_extractor_timeout_falls_back_to_ocr(self):
        self.text_patch.stop()
        with patch.object(ocr.subprocess, 'run', side_effect=subprocess.TimeoutExpired('pdftotext', 30)):
            with self.assertLogs('uvicorn.error', level='WARNING'):
                self.assertEqual(ocr.read_text_pages('batch.pdf', 2), ['', ''])


if __name__ == '__main__':
    unittest.main()
