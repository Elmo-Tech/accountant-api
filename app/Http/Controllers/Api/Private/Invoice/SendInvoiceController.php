<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Services\Upload\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;


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
            'files.*' => 'required|mimes:pdf|max:10240',
        ]);

        $httpRequest = Http::asMultipart();
        $fileNames = [];

        foreach ($request->file('files') as $i => $file) {
            $uploadedPath = $this->uploadService->uploadFile($file, 'uploadedInvoices');
            $fullPath = storage_path('app/public/' . $uploadedPath);
            $originalName = $file->getClientOriginalName();
            $fileNames[] = $originalName;

            $httpRequest->attach(
                "files[$i]",
                file_get_contents($fullPath),
                $originalName
            );
        }

        try {
            $response = $httpRequest->post('https://safa.masar-soft.com/api/v1/read-cf');

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to process files on remote server',
                    'details' => $response->body(),
                ], $response->status());
            }

            return response()->json([
                'message' => 'All files processed successfully',
                'results' => $response->json(),
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
