<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice\Invoice;
use Illuminate\Http\Request;class PaidInvoicesTotalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function __invoke(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'nullable',
                'endDate'   => 'nullable',
                'year'      => 'nullable|integer|min:2000',
                'clientId'  => 'nullable|integer|exists:clients,id',
            ]);

            $totalAmountCollected = $this->calcTotal(1, $request->startDate, $request->endDate, $request->year, 'invoices.pay_date', $request->clientId);
            $totalInvoicesAmount  = $this->calcTotal(null, $request->startDate, $request->endDate, $request->year, 'invoices.end_at', $request->clientId);
            $totalUncollected     = $this->calcTotal(0, $request->startDate, $request->endDate, $request->year, 'invoices.end_at', $request->clientId);

            return response()->json([
                'totalAmountCollected' => $totalAmountCollected,
                'totalInvoicesAmount'  => $totalInvoicesAmount,
                'totalUncollected'     => $totalUncollected,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function calcTotal(?int $paidStatus, ?string $startDate, ?string $endDate, ?string $year, string $dateColumn, ?int $clientId = null): float
    {
        $start = $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : null;
        $end   = $endDate   ? \Carbon\Carbon::parse($endDate)->format('Y-m-d')   : null;

        $invoices = Invoice::with(['invoiceDetails', 'client'])
            ->whereHas('client')->whereHas('invoiceDetails')
            ->when(!is_null($paidStatus), fn($q) => $q->where('pay_status', $paidStatus))
            ->whereNull('invoices.deleted_at')
            ->when($paidStatus === 0, fn($q) => $q->where(fn($q2) =>
                $q2->whereNotNull('invoices.end_at')->where('invoices.end_at', '<=', now()->toDateString())
            ))
            ->when($start,    fn($q) => $q->whereDate($dateColumn, '>=', $start))
            ->when($end,      fn($q) => $q->whereDate($dateColumn, '<=', $end))
            ->when($year,     fn($q) => $q->whereYear($dateColumn, $year))
            ->when($clientId, fn($q) => $q->where('invoices.client_id', $clientId))
            ->get();

        $total = 0;

        foreach ($invoices as $invoice) {
            $total += app(\App\Services\Invoice\InvoiceTotalsService::class)->forInvoice($invoice)['total'];
        }

        return round($total, 2);
    }
}
