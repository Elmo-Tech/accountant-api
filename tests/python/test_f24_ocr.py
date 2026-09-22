import base64
from io import BytesIO
import importlib.util
from pathlib import Path
import unittest
from unittest.mock import patch

from fastapi.testclient import TestClient
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

    def upload(self, files):
        return self.client.post('/read-cf', headers={'X-API-Key': 'test-only-key'}, files=files)

    def test_repeated_client_and_other_client_get_independent_original_pages(self):
        texts = ['invoice A', 'invoice B', 'invoice C', 'invoice D']
        codes = ['LHDMMD97T01Z336N', 'RSSMRA80A01H501U', 'LHDMMD97T01Z336N', 'LHDMMD97T01Z336N']
        with patch.object(ocr, 'read_page_cf', side_effect=codes) as read:
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
        with patch.object(ocr, 'read_page_cf', side_effect=['LHDMMD97T01Z336N', None, RuntimeError('OCR timeout'), 'RSSMRA80A01H501U']):
            with self.assertLogs('uvicorn.error', level='ERROR'):
                result = self.upload([('files[]', ('batch.pdf', pdf_pages(['a', 'b', 'c', 'd']), 'application/pdf'))]).json()[0]
        self.assertEqual(result['invoices'][1]['status'], 'not_found')
        self.assertIsNone(result['invoices'][1]['cf'])
        self.assertNotIn('pdf_base64', result['invoices'][1])
        self.assertFalse(result['invoices'][2]['success'])
        self.assertTrue(result['invoices'][3]['success'])

    def test_bad_file_does_not_stop_next_upload_with_same_name(self):
        with patch.object(ocr, 'read_page_cf', return_value='LHDMMD97T01Z336N'):
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


if __name__ == '__main__':
    unittest.main()
