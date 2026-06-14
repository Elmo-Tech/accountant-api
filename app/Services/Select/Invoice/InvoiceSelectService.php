<?php

namespace App\Services\Select\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceSelectService
{
    public function getAllInvoices(?int $clientId = null)
    {
        return DB::table('invoices')
            ->leftJoin('invoice_details', function($join) {
                $join->on('invoices.id', '=', 'invoice_details.invoice_id')
                     ->where('invoice_details.invoiceable_type', '=', 'App\\Models\\Client\\ClientPayInstallment')
                     ->whereNull('invoice_details.deleted_at');
            })
            ->leftJoin('client_pay_installments', 'invoice_details.invoiceable_id', '=', 'client_pay_installments.id')
            ->select([
                'invoices.id as value',
                DB::raw("CONCAT(invoices.number, ' - ', DATE_FORMAT(COALESCE(MAX(client_pay_installments.start_at), MIN(invoices.created_at)), '%d/%m/%Y')) as label")
            ])
            ->whereNull('invoices.deleted_at')
            ->when($clientId !== null, function ($query) use ($clientId) {
                $query->where('invoices.client_id', $clientId);
            })
            ->groupBy('invoices.id', 'invoices.number')
            ->get();
    }

}

