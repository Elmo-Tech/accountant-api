<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Services\Upload\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class SendInvoiceController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|mimes:pdf|max:10240',
        ]);

        $httpRequest = Http::acceptJson()->asMultipart()
            ->withHeaders(['X-API-Key' => 'cf21978a406f3dd83f265498a48bd8113dc5da235c18e37db55ae3dec254649d'])
            ->connectTimeout(10)
            ->timeout((int) config('services.f24_ocr.timeout', 120));

        foreach ($request->file('files') as $file) {
            $uploadedPath = $this->uploadService->uploadFile($file, 'uploadedInvoices');
            $fullPath = Storage::disk('public')->path($uploadedPath);
            $originalName = $file->getClientOriginalName();

            $httpRequest->attach(
                'files[]',
                file_get_contents($fullPath),
                $originalName,
                ['Content-Type' => 'application/pdf']
            );
        }

        try {
            $response = $httpRequest->post(config('services.f24_ocr.url'));

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to process files on remote server',
                    'details' => $response->body(),
                ], $response->status());
            }

            $results = $response->json();

            if (! is_array($results) || ! array_is_list($results)
                || count($results) !== count($request->file('files'))
                || collect($results)->contains(fn ($result) => ! is_array($result)
                    || ! isset($result['file']) || ! is_bool($result['success'] ?? null))) {
                return response()->json(['error' => 'Invalid response from F24 OCR service'], 502);
            }

            $hasErrors = collect($results)->contains(fn ($result) => $result['success'] === false);

            return response()->json([
                'message' => $hasErrors ? 'Some files could not be processed' : 'All files processed successfully',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Connection error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



   /* public function index(Request $request)
{
    $request->validate([
        'files.*' => 'required|mimes:pdf|max:10240',
    ]);

    $results = [];

    foreach ($request->file('files') as $uploaded) {
        $uploadedFile = $this->uploadService->uploadFile($uploaded, 'uploadedInvoices');
        $pdfPath = storage_path('app/public/' . $uploadedFile);

        try {
            $pythonScript = base_path('app/Http/Controllers/Api/Private/Invoice/image_pro.py');

            // Choose appropriate python command based on OS
            $pythonPath = PHP_OS_FAMILY === 'Windows' ? 'C:\\Python312\\python.exe' : 'python3';

            $command = [$pythonPath, $pythonScript, $pdfPath];
            $process = Process::run($command);

            if ($process->failed()) {
                $results[] = [
                    'file' => $uploaded->getClientOriginalName(),
                    'error' => 'Python script failed',
                    'stderr' => $process->errorOutput(),
                    'stdout' => $process->output(),
                ];

                continue;
            }

            $stdout = trim($process->output());

            $cfData = json_decode($stdout, true);


            if (!isset($cfData['cf']) || !$cfData['cf']) {

                $results[] = [
                    'file' => $uploaded->getClientOriginalName(),
                    'codice_fiscale' => $cfData,
                    'status' => 'processed',
                ];

                continue;
            }


            $client = Client::where('cf', $cfData['cf'])->first();
            if ($client) {
                $this->sendInvoiceToClient($client->email, $pdfPath);
            }

            $results[] = [
                'file' => $uploaded->getClientOriginalName(),
                'codice_fiscale' => $cfData['cf'],
                'status' => 'processed',
            ];
        } catch (\Exception $e) {
            $results[] = [
                'file' => $uploaded->getClientOriginalName(),
                'error' => $e->getMessage()
            ];
        }
    }

    return response()->json([
        'message' => 'All files processed',
        'results' => $results
    ]);
}*/

    /**
     * Send the extracted invoice PDF to the client.
     */
    private function sendInvoiceToClient($email, $pdfPath)
    {
        Mail::raw('Here is your invoice.', function ($message) use ($email, $pdfPath) {
            $message->to($email)
                ->subject('Your Invoice')
                ->attach($pdfPath, [
                    'as' => 'invoice.pdf',
                    'mime' => 'application/pdf',
                ]);
        });
    }
}
