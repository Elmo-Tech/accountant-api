<?php
namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Services\Upload\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;


class ImageToExcelController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function index(Request $request)
    {
        // Validate that a PDF file is uploaded
        $request->validate([
            'path' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        // Store the uploaded PDF file
        $pdf = $request->file('path');
        $filename = $pdf->getClientOriginalName();
        $path = $pdf->storeAs('uploads', $filename, 'public');

        // Get absolute path for the Python script and uploaded PDF
        $absolutePath = storage_path('app/public/' . $path);
        $pythonScript = base_path('app/Http/Controllers/Api/Private/Invoice/image_pro2.py');
        $pythonPath = 'C:\Python312\python.exe';

        // Prepare command to run the Python script
        $command = "\"$pythonPath\" \"$pythonScript\" \"$absolutePath\"";

        $process = Process::run($command);


        // Handle failure
        if (!$process->successful()) {
            return response()->json([
                'error' => 'Python script failed',
                'output' => $process->output(),
                'errorOutput' => $process->errorOutput(),
            ], 500);
        }

        // Return the generated Excel file
        $outputFile = storage_path('app/public/output.xlsx');
        if (!file_exists($outputFile)) {
            return response()->json(['error' => 'Output file not found'], 404);
        }

        return response()->download($outputFile)->deleteFileAfterSend(true);
    }


}
