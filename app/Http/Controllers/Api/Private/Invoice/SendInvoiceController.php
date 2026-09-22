<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendInvoiceController extends Controller
{
    private const OCR_URL =
        'https://f24-ocr.elmotechsoft.online/read-cf';

    private const OCR_API_KEY =
        'cf21978a406f3dd83f265498a48bd8113dc5da235c18e37db55ae3dec254649d';

    private const TEMP_EMAILS = [
        'MOHAMEDELHADDAD997@gmail.com',
        'mr10dev10@gmail.com',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf|max:10240',
        ]);

        $files = array_values($request->file('files'));
        $httpRequest = Http::acceptJson()->asMultipart()
            ->withHeaders(['X-API-Key' => self::OCR_API_KEY])
            ->connectTimeout(10)->timeout(600);

        // Stream from PHP's upload temporary files; duplicate names cannot
        // overwrite another upload and no public copy of the batch is needed.
        foreach ($files as $file) {
            $httpRequest->attach('files[]', file_get_contents($file->getRealPath()),
                $file->getClientOriginalName(), ['Content-Type' => 'application/pdf']);
        }

        try {
            $response = $httpRequest->post(self::OCR_URL);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Connection error', 'details' => $e->getMessage()], 500);
        }
        if (! $response->successful()) {
            return response()->json([
                'error' => 'Failed to process files on remote server',
                'status' => $response->status(), 'details' => $response->body(),
            ], $response->status());
        }

        $ocrResults = $response->json();
        // Validate the entire response before sending anything. The old service
        // returns just one CF per upload and cannot safely process a batch.
        if (! $this->validOcrResults($ocrResults, count($files))) {
            return response()->json([
                'error' => 'Invalid response from F24 OCR service; update the container to return per-page invoices',
            ], 502);
        }

        $results = [];
        foreach ($ocrResults as $index => $ocrResult) {
            $originalName = $files[$index]->getClientOriginalName();
            if (! $ocrResult['success']) {
                $results[] = [
                    'file' => $originalName, 'file_index' => $index, 'success' => false,
                    'cf' => null, 'status' => 'error', 'client_found' => false, 'email_sent' => false,
                    'error' => $ocrResult['error'] ?? 'Unable to process PDF',
                ];

                continue;
            }

            // Every occurrence gets its own attachment and message, even when
            // several invoices belong to the same client.
            foreach ($ocrResult['invoices'] as $invoice) {
                $result = [
                    'file' => $originalName, 'file_index' => $index, 'page' => $invoice['page'],
                    'success' => $invoice['success'], 'cf' => $invoice['cf'] ?? null,
                    'status' => $invoice['status'] ?? null, 'client_found' => false, 'email_sent' => false,
                ];
                if (! $invoice['success']) {
                    $result['error'] = $invoice['error'] ?? 'Unable to process PDF page';
                } elseif (empty($invoice['cf'])) {
                    $result['message'] = $invoice['message'] ?? 'Codice Fiscale not found';
                } else {
                    $cf = strtoupper(preg_replace('/\s+/', '', $invoice['cf']));
                    $result['cf'] = $cf;
                    $pdf = base64_decode($invoice['pdf_base64'] ?? '', true);
                    if ($pdf === false || ! str_starts_with($pdf, '%PDF-')) {
                        $result['success'] = false;
                        $result['error'] = 'Missing or invalid invoice PDF';
                    } elseif (! Client::where('cf', $cf)->exists()) {
                        $result['message'] = 'Client not found for this Codice Fiscale';
                    } else {
                        $result['client_found'] = true;
                        $stem = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                        $fileName = $stem.'_file_'.($index + 1).'_page_'.$invoice['page'].'.pdf';
                        $result['invoice_file'] = $fileName;
                        try {
                            $this->sendInvoiceToTemporaryEmails($pdf, $fileName);
                            $result['email_sent'] = true;
                            $result['emails'] = self::TEMP_EMAILS;
                        } catch (\Throwable $e) {
                            $result['email_error'] = $e->getMessage();
                        }
                    }
                }
                $results[] = $result;
            }
        }

        $hasWarnings = collect($results)->contains(fn ($result) => ! $result['email_sent']);

        return response()->json([
            'message' => $hasWarnings ? 'Files processed with some warnings' : 'All files processed successfully',
            'results' => $results,
        ]);
    }

    private function validOcrResults($results, int $fileCount): bool
    {
        if (! is_array($results) || ! array_is_list($results) || count($results) !== $fileCount) {
            return false;
        }
        foreach ($results as $file) {
            if (! is_array($file) || ! isset($file['success']) || ! is_bool($file['success'])) {
                return false;
            }
            if (! $file['success']) {
                continue;
            }
            if (! isset($file['page_count'], $file['invoices'])
                || ! is_int($file['page_count']) || $file['page_count'] < 1
                || ! is_array($file['invoices']) || ! array_is_list($file['invoices'])
                || count($file['invoices']) !== $file['page_count']) {
                return false;
            }
            foreach ($file['invoices'] as $index => $invoice) {
                if (! is_array($invoice) || ($invoice['page'] ?? null) !== $index + 1
                    || ! isset($invoice['success']) || ! is_bool($invoice['success'])
                    || (isset($invoice['cf']) && ! is_string($invoice['cf']))
                    || (isset($invoice['pdf_base64']) && ! is_string($invoice['pdf_base64']))) {
                    return false;
                }
            }
        }

        return true;
    }

    private function sendInvoiceToTemporaryEmails(string $pdf, string $fileName): void
    {
        Mail::raw('Here is your invoice.', function ($message) use ($pdf, $fileName) {
            $message->to(self::TEMP_EMAILS)->subject('Your Invoice')
                ->attachData($pdf, $fileName, ['mime' => 'application/pdf']);
        });
    }
}
