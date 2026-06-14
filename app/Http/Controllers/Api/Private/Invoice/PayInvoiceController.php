<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice\Invoice;
use Illuminate\Http\Request;

class PayInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'invoiceId' => 'required|exists:invoices,id',
                'payStatus' => 'required|in:0,1',
                'payDate' => 'nullable|date'
            ]);

            $invoice = Invoice::findOrFail($request->invoiceId);

            $invoice->pay_status = $request->payStatus;
            $invoice->pay_date = $request->payDate;
            $invoice->save();

            return response()->json([
                'message' => __('messages.success.updated')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
