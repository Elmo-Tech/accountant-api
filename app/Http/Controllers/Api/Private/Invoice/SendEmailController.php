<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceEmail;
use App\Models\Client\Client;
use App\Models\Invoice\Invoice;

use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class SendEmailController extends Controller
{

    protected $reportService;
    public function  __construct(ReportService $reportService)
    {
        //$this->middleware('auth:api');
        //$this->middleware('permission:all_reports', ['only' => ['__invoke']]);
        $this->reportService =$reportService;
    }
    public function index(Request $request)
    {
        // Validate request data
        $request->validate([
            'email' => 'required:email',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,csv',
        ]);

        DB::beginTransaction();
        try {
            $attachments = $request->file('attachments') ?? [];
            $storedAttachments = [];
            $storedAttachmentsToDelete = [];

            // Store files in the 'uploads' disk and prepare the paths for email attachments
            foreach ($attachments as $file) {
                // Store the file in the 'uploads' disk with the original file name
                $filePath = $file->storeAs('attachments', $file->getClientOriginalName(), 'public');

                // Get the full storage path for the file (to attach in email)
                $absolutePath = Storage::disk('public')->path($filePath);

                // Store the absolute path for email attachments
                $storedAttachments[] = $absolutePath;

                // Keep track of files to delete
                $storedAttachmentsToDelete[] = $filePath;
            }

            // Send email using Mailable with the actual file paths for attachments
            Mail::to($request->email)->send(new InvoiceEmail(
                $request->subject,
                $request->content,
                $storedAttachments // Pass the array of actual file paths
            ));

            DB::commit();

            // Delete the files after sending the email
            Storage::disk('public')->delete($storedAttachmentsToDelete);

            return response()->json(['message' => 'Email Sent!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to send email', 'message' => $e->getMessage()], 500);
        }
    }
}
