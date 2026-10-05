<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientContact;
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
        'mr10dev10@gmail.com',
        // 'angela@elaborazionistudio.com',
    ];

    private const TEMP_BCC_EMAILS = [
        // 'mohamedelhaddad997@gmail.com',
    ];

    private const PRESENTATION_TYPES = ['cartacea', 'telematico_entratel'];

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
        $clientEmails = [];
        foreach ($ocrResults as $index => $ocrResult) {
            $originalName = $files[$index]->getClientOriginalName();
            if (! $ocrResult['success']) {
                $results[] = [
                    'file' => $originalName, 'file_index' => $index, 'success' => false,
                    'cf' => null, 'status' => 'error', 'client_found' => false, 'contact_found' => false,
                    'matched_in' => null, 'matched_id' => null, 'email_sent' => false,
                    'error' => $ocrResult['error'] ?? 'Unable to process PDF',
                ];

                continue;
            }

            // Keep each invoice as a separate PDF, but collect attachments by
            // normalized CF and presentation type across all uploaded files.
            foreach ($ocrResult['invoices'] as $invoice) {
                $result = [
                    'file' => $originalName, 'file_index' => $index, 'page' => $invoice['page'],
                    'success' => $invoice['success'], 'cf' => $invoice['cf'] ?? null,
                    'status' => $invoice['status'] ?? null, 'client_found' => false, 'email_sent' => false,
                    'invoice_number' => null, 'due_date' => null,
                    'contact_found' => false, 'matched_in' => null, 'matched_id' => null,
                    'presentation_type' => $invoice['presentation_type'] ?? null,
                ];
                $number = $invoice['invoice_number'] ?? null;
                $date = $invoice['due_date'] ?? null;
                $parsedDate = is_string($date) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date)
                    ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
                $result['invoice_number'] = is_string($number) && preg_match('/^[0-9]+$/D', $number) ? $number : null;
                $result['due_date'] = $parsedDate && $parsedDate->format('Y-m-d') === $date ? $date : null;
                if ($result['invoice_number'] === null || $result['due_date'] === null) {
                    $result['metadata_warning'] = 'Invoice number or due date could not be read from Riferimento';
                }
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
                    } else {
                        // Match identity only. Never use client/contact email addresses
                        // for recipients while this workflow is in test mode.
                        $client = Client::where('cf', $cf)->first(['id']);
                        $contact = $client ? null : ClientContact::where('cf', $cf)->first(['id']);
                        $result['client_found'] = $client !== null;
                        $result['contact_found'] = $contact !== null;
                        $result['matched_in'] = $client ? 'clients' : ($contact ? 'contacts' : null);
                        $result['matched_id'] = ($client ?? $contact)?->id;
                        if (! $client && ! $contact) {
                            $result['message'] = 'Client or contact not found for this Codice Fiscale';
                            $results[] = $result;

                            continue;
                        }
                        $presentationType = $result['presentation_type'];
                        if (! in_array($presentationType, self::PRESENTATION_TYPES, true)) {
                            $result['message'] = 'Missing or ambiguous F24 presentation type; email not sent';
                            $results[] = $result;

                            continue;
                        }
                        $stem = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                        $fileName = $stem.'_file_'.($index + 1).'_page_'.$invoice['page'].'.pdf';
                        $result['invoice_file'] = $fileName;
                        $groupKey = 'cf:'.$cf.':'.$presentationType;
                        $clientEmails[$groupKey]['presentation_type'] = $presentationType;
                        $clientEmails[$groupKey]['attachments'][] = [
                            'pdf' => $pdf, 'name' => $fileName,
                            'invoice_number' => $result['invoice_number'], 'due_date' => $result['due_date'],
                        ];
                        $clientEmails[$groupKey]['result_indexes'][] = count($results);
                    }
                }
                $results[] = $result;
            }
        }

        // Test recipients are shared, but invoices from different clients must
        // still be sent in different messages.
        foreach ($clientEmails as $clientEmail) {
            try {
                $this->sendInvoicesToTemporaryEmails($clientEmail['attachments'], $clientEmail['presentation_type']);
                foreach ($clientEmail['result_indexes'] as $resultIndex) {
                    $results[$resultIndex]['email_sent'] = true;
                    $results[$resultIndex]['emails'] = self::TEMP_EMAILS;
                }
            } catch (\Throwable $e) {
                foreach ($clientEmail['result_indexes'] as $resultIndex) {
                    $results[$resultIndex]['email_error'] = $e->getMessage();
                }
            }
        }

        $hasWarnings = collect($results)->contains(fn ($result) => ! $result['email_sent'] || isset($result['metadata_warning']));

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
                    || (isset($invoice['due_date']) && ! is_string($invoice['due_date']))
                    || (isset($invoice['invoice_number']) && ! is_string($invoice['invoice_number']))
                    || (isset($invoice['presentation_type']) && ! is_string($invoice['presentation_type']))
                    || (isset($invoice['pdf_base64']) && ! is_string($invoice['pdf_base64']))) {
                    return false;
                }
            }
        }

        return true;
    }

    private function sendInvoicesToTemporaryEmails(array $attachments, string $presentationType): void
    {
        // Use the first attached invoice in upload/page order, not the earliest
        // date or a date from another presentation group for the same person.
        $firstDueDate = $attachments[0]['due_date'] ?? null;
        $dueDate = $firstDueDate
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $firstDueDate)->format('d/m/Y')
            : null;
        $body = "Gentile Cliente,\n\nin allegato il modello F24";
        $body .= $dueDate
            ? ' in scadenza il '.$dueDate.'.'
            : '. La data di scadenza non è disponibile.';
        if ($presentationType === 'telematico_entratel') {
            $body .= "\nAttendiamo la solita autorizzazione per procedere con l’addebito telematico.";
        }
        $body .= "\n\nCordiali saluti.\nElaborazioni Srl\nVia Stazione, 9/D\nCrema (CR)\nTel.+39 0373 86998";
        $subject = $dueDate ? 'Modelli F24 in scadenza - '.$dueDate : 'Invio modelli F24';

        Mail::raw($body, function ($message) use ($attachments, $subject) {
            $message->from(config('mail.from.address'), 'Servizio F24')
                ->to(self::TEMP_EMAILS)->subject($subject);
            if (self::TEMP_BCC_EMAILS !== []) {
                $message->bcc(self::TEMP_BCC_EMAILS);
            }
            foreach ($attachments as $attachment) {
                $message->attachData($attachment['pdf'], $attachment['name'], ['mime' => 'application/pdf']);
            }
        });
    }
}
