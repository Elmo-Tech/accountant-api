<?php

namespace App\Services\Invoice;

use App\Models\Client\ClientBankAccount;
use App\Models\Invoice\Invoice;
use App\Models\Parameter\ParameterValue;
use Illuminate\Support\Facades\DB;

class InvoicePaymentService
{
    public function current(Invoice $invoice): array
    {
        $clientBank = ClientBankAccount::with('bank')
            ->where('client_id', $invoice->client_id)
            ->orderByDesc('is_main')->orderBy('id')->first();
        $method = ParameterValue::find($invoice->payment_type_id);
        // A missing/deleted selected account must never silently become another account.
        $bank = $invoice->bank_account_id !== null
            ? ParameterValue::find($invoice->bank_account_id)
            : ParameterValue::where('parameter_order', 7)->where('is_default', 1)->orderBy('id')->first();

        return [
            'paymentMethod' => $method?->code ?? '',
            'paymentMethodName' => $method?->parameter_value ?? '',
            'bankAccount' => [
                'iban' => $bank?->parameter_value ?? '',
                'abi' => $bank?->description2 ?? '',
                'cab' => $bank?->description3 ?? '',
                'bankName' => $bank?->description ?? '',
            ],
            'clientBankAccount' => [
                'iban' => $clientBank?->iban ?? '',
                'abi' => $clientBank?->abi ?? '',
                'cab' => $clientBank?->cab ?? '',
                'bankName' => $clientBank?->bank?->parameter_value ?? '',
            ],
        ];
    }

    public function forDocument(Invoice $invoice): array
    {
        if (trim((string) $invoice->invoice_xml_number) === '') {
            return $this->current($invoice);
        }

        if ($invoice->payment_snapshot !== null) {
            return $invoice->payment_snapshot;
        }

        // Fallback for legacy/imported numbered invoices; never overwrite a snapshot.
        return DB::transaction(function () use ($invoice) {
            $locked = Invoice::withTrashed()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->payment_snapshot === null) {
                $locked->payment_snapshot = $this->current($locked);
                $locked->timestamps = false;
                $locked->save();
            }
            $invoice->payment_snapshot = $locked->payment_snapshot;

            return $locked->payment_snapshot;
        });
    }
}
