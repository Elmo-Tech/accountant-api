<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceEmail;
use App\Models\Client\Client;
use App\Models\Invoice\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ClientEmailController extends Controller
{


    public function edit(Request $request)
    {
        $invoice = Invoice::find($request->invoiceId);

        $client = Client::find($invoice->client_id);

        return response()->json([
            'email' => $client->email??""
        ]);
    }
}
