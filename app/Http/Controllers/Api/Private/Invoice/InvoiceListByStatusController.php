<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\InvoiceListCollection;
use App\Models\Invoice\Invoice;
use App\Utils\PaginateCollection;
use Illuminate\Http\Request;

class InvoiceListByStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // key: 0 = all, 1 = paid, 2 = unpaid
    public function __invoke(Request $request)
    {
        try {
            $request->validate([
                'type'      => 'required|in:0,1,2',
                'startDate' => 'nullable',
                'endDate'   => 'nullable',
                'year'      => 'nullable|integer|min:2000',
                'clientId'  => 'nullable|integer|exists:clients,id',
                'pageSize'  => 'nullable|integer|min:1',
                'page'      => 'nullable|integer|min:1',
            ]);

            $key = (int) $request->type;

            // date column depends on key
            $dateColumn = $key === 1 ? 'invoices.pay_date' : 'invoices.end_at';

            $invoices = Invoice::with(['client', 'invoiceDetails'])
                ->whereHas('client')->whereHas('invoiceDetails')
                ->whereNull('invoices.deleted_at')
                ->when($key === 1, fn($q) => $q->where('pay_status', 1))
                ->when($key === 2, fn($q) => $q->where('pay_status', 0)
                    ->whereNotNull('invoices.end_at')
                    ->where('invoices.end_at', '<=', now()->toDateString()))
                ->when($request->filled('startDate'), fn($q) => $q->whereDate($dateColumn, '>=', \Carbon\Carbon::parse($request->startDate)->format('Y-m-d')))
                ->when($request->filled('endDate'),   fn($q) => $q->whereDate($dateColumn, '<=', \Carbon\Carbon::parse($request->endDate)->format('Y-m-d')))
                ->when($request->filled('year'),      fn($q) => $q->whereYear($dateColumn, $request->year))
                ->when($request->filled('clientId'),  fn($q) => $q->where('invoices.client_id', $request->clientId))
                ->get();

            $invoices = app(\App\Services\Invoice\InvoiceListService::class)->ordered($invoices);
            $data = $invoices->map(function ($invoice) use ($key) {
                $date = $key === 1
                    ? $invoice->pay_date
                    : $invoice->end_at;

                $amounts = app(\App\Services\Invoice\InvoiceTotalsService::class)->forInvoice($invoice);

                return [
                    'invoiceId'     => $invoice->id,
                    'invoiceNumber' => $invoice->number ?? '',
                    'clientName'    => $invoice->client->ragione_sociale ?? '',
                    'date'          => $date,
                    'total'         => $amounts['total'],
                    'taxableAmount' => $amounts['taxableAmount'],
                    'ivaAmount' => $amounts['ivaAmount'],
                    'payStatus'     => $invoice->pay_status,
                ];
            });

            $totals = app(\App\Services\Invoice\InvoiceTotalsService::class)->summarize($data, 'total');
            $pageSize = $request->pageSize ?? 10;
            $paginated = PaginateCollection::paginate(collect($data), $pageSize);

            return response()->json(new InvoiceListCollection($paginated, $totals), 200);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
