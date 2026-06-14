<?php

namespace App\Http\Controllers\Api\Private\Select;

use App\Http\Controllers\Controller;
use App\Models\Invoice\Invoice;
use App\Services\Select\SelectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectController extends Controller
{
    private $selectService;

    public function __construct(SelectService $selectService)
    {
        $this->selectService = $selectService;
    }

    public function getSelects(Request $request)
    {
        $selectData = $this->selectService->getSelects($request->allSelects);

        return response()->json($selectData);
    }

    public function getAllInvoices(Request $request){
        $invoicesData = [];

        foreach ($request->clientIds as $clientId) {
            $invoicesData[] = [
                'label' => 'invoices-' . $clientId,
                'options' => DB::table('invoices')
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
                    ->where('invoices.client_id', $clientId)
                    ->whereNull('invoices.deleted_at')
                    ->groupBy('invoices.id', 'invoices.number')
                    ->get()
                    ->toArray()
            ];
        }

        return response()->json($invoicesData);
    }


}
