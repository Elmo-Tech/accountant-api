<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Services\Upload\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendInvoiceController extends Controller
{
    private const OCR_URL =
        'https://f24-ocr.elmotechsoft.online/read-cf';

    private const OCR_API_KEY =
        'cf21978a406f3dd83f265498a48bd8113dc5da235c18e37db55ae3dec254649d';

    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate files
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf|max:10240',
        ]);

        $files = $request->file('files');

        /*
        |--------------------------------------------------------------------------
        | Prepare HTTP request
        |--------------------------------------------------------------------------
        */

        $httpRequest = Http::acceptJson()
            ->asMultipart()
            ->withHeaders([
                'X-API-Key' => self::OCR_API_KEY,
            ])
            ->connectTimeout(10)
            ->timeout(120);

        /*
        |--------------------------------------------------------------------------
        | Store local files
        |--------------------------------------------------------------------------
        */

        $uploadedFiles = [];

        foreach ($files as $file) {

            $uploadedPath = $this->uploadService->uploadFile(
                $file,
                'uploadedInvoices'
            );

            $fullPath = Storage::disk('public')->path(
                $uploadedPath
            );

            $originalName = $file->getClientOriginalName();

            $uploadedFiles[] = [
                'name' => $originalName,
                'path' => $fullPath,
                'storage_path' => $uploadedPath,
            ];

            /*
            |--------------------------------------------------------------------------
            | Attach PDF to Python request
            |--------------------------------------------------------------------------
            */

            $httpRequest->attach(
                'files[]',
                file_get_contents($fullPath),
                $originalName,
                [
                    'Content-Type' => 'application/pdf',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send files to Python OCR API
        |--------------------------------------------------------------------------
        */

        try {

            $response = $httpRequest->post(
                self::OCR_URL
            );

        } catch (\Throwable $e) {

            return response()->json([
                'error' => 'Connection error',
                'details' => $e->getMessage(),
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Check remote response
        |--------------------------------------------------------------------------
        */

        if (! $response->successful()) {

            return response()->json([
                'error' => 'Failed to process files on remote server',
                'status' => $response->status(),
                'details' => $response->body(),
            ], $response->status());
        }

        $ocrResults = $response->json();

        /*
        |--------------------------------------------------------------------------
        | Validate Python response
        |--------------------------------------------------------------------------
        */

        if (
            ! is_array($ocrResults)
            || count($ocrResults) !== count($uploadedFiles)
        ) {

            return response()->json([
                'error' => 'Invalid response from F24 OCR service',
                'ocr_response' => $ocrResults,
            ], 502);
        }

        /*
        |--------------------------------------------------------------------------
        | Process each OCR result
        |--------------------------------------------------------------------------
        */

        $results = [];

        foreach ($ocrResults as $index => $ocrResult) {

            $uploadedFile = $uploadedFiles[$index];

            $result = [
                'file' => $uploadedFile['name'],
                'success' => $ocrResult['success'] ?? false,
                'cf' => $ocrResult['cf'] ?? null,
                'status' => $ocrResult['status'] ?? null,
            ];

            /*
            |--------------------------------------------------------------------------
            | OCR failed
            |--------------------------------------------------------------------------
            */

            if (($ocrResult['success'] ?? false) === false) {

                $result['error'] =
                    $ocrResult['error']
                    ?? 'Unable to process PDF';

                $results[] = $result;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Codice Fiscale not found
            |--------------------------------------------------------------------------
            */

            $cf = $ocrResult['cf'] ?? null;

            if (! $cf) {

                $result['message'] =
                    $ocrResult['message']
                    ?? 'Codice Fiscale not found';

                $result['client_found'] = false;
                $result['email_sent'] = false;

                $results[] = $result;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Find client
            |--------------------------------------------------------------------------
            */

            $client = Client::where('cf', $cf)->first();

            if (! $client) {

                $result['client_found'] = false;
                $result['email_sent'] = false;
                $result['message'] =
                    'Client not found for this Codice Fiscale';

                $results[] = $result;

                continue;
            }

            $result['client_found'] = true;

            /*
            |--------------------------------------------------------------------------
            | Check client email
            |--------------------------------------------------------------------------
            */

            if (! $client->email) {

                $result['email_sent'] = false;
                $result['message'] =
                    'Client found but email is missing';

                $results[] = $result;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Send invoice email
            |--------------------------------------------------------------------------
            */

            try {

                $this->sendInvoiceToClient(
                    $client->email,
                    $uploadedFile['path'],
                    $uploadedFile['name']
                );

                $result['email_sent'] = true;
                $result['email'] = $client->email;

            } catch (\Throwable $e) {

                $result['email_sent'] = false;
                $result['email_error'] = $e->getMessage();
            }

            $results[] = $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Final response
        |--------------------------------------------------------------------------
        */

        $hasErrors = collect($results)->contains(
            function ($result) {
                return
                    ($result['success'] ?? false) === false
                    || ($result['cf'] ?? null) === null
                    || ($result['client_found'] ?? false) === false
                    || ($result['email_sent'] ?? false) === false;
            }
        );

        return response()->json([
            'message' => $hasErrors
                ? 'Files processed with some warnings'
                : 'All files processed successfully',
            'results' => $results,
        ]);
    }

    /**
     * Send invoice PDF to client.
     */
    private function sendInvoiceToClient(
        string $email,
        string $pdfPath,
        string $fileName
    ): void {
        Mail::raw(
            'Here is your invoice.',
            function ($message) use (
                $email,
                $pdfPath,
                $fileName
            ) {
                $message
                    ->to($email)
                    ->subject('Your Invoice')
                    ->attach(
                        $pdfPath,
                        [
                            'as' => $fileName,
                            'mime' => 'application/pdf',
                        ]
                    );
            }
        );
    }
}
