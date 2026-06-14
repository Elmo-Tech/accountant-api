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

            $data = $invoices->map(function ($invoice) use ($key) {
                $date = $key === 1
                    ? $invoice->pay_date
                    : $invoice->end_at;

                // calc total same as InvoiceController
                $subtotal = $invoice->invoiceDetails->sum('price_after_discount');
                $subtotal = $subtotal + ($subtotal * 0.22);

                if ($invoice->client && $invoice->client->total_tax > 0) {
                    $subtotal = $subtotal + ($subtotal * ($invoice->client->total_tax / 100));
                }

                if ($invoice->discount_amount > 0) {
                    if ($invoice->discount_type == 0) {
                        $subtotal -= $invoice->discount_amount;
                    } elseif ($invoice->discount_type == 1) {
                        $subtotal -= $subtotal * ($invoice->discount_amount / 100);
                    }
                }

                return [
                    'invoiceId'     => $invoice->id,
                    'invoiceNumber' => $invoice->number ?? '',
                    'clientName'    => $invoice->client->ragione_sociale ?? '',
                    'date'          => $date,
                    'total'         => round($subtotal, 2),
                    'payStatus'     => $invoice->pay_status,
                ];
            });

            $pageSize = $request->pageSize ?? 10;
            $paginated = PaginateCollection::paginate(collect($data), $pageSize);

            return response()->json(new InvoiceListCollection($paginated), 200);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
