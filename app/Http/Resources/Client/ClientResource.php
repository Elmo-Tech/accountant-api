<?php

namespace App\Http\Resources\Client;

use App\Http\Resources\Client\ClientAddress\AllClientAddressResource;
use App\Http\Resources\Client\ClientContact\AllClientContactResource;
use App\Http\Resources\Client\PayInstallment\AllPayInstallmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'clientId' => $this->id,
            'ragioneSociale' => $this->ragione_sociale??"",
            'iva' => $this->iva??"",
            'cf' => $this->cf??"",
            'note' => $this->note??"",
            'phone' => $this->phone??"",
            'email' => $this->email??"",
            'emailF24' => $this->email_f24??"",
            'price' => $this->price??0,
            'monthlyPrice' => $this->monthly_price??0,
            'hoursPerMonth' => $this->hours_per_month??0,
            'paymentTypeId' => $this->payment_type_id??"" ,
            'payStepsId'=> $this->pay_steps_id??"",
            'paymentTypeTwoId'=> $this->payment_type_two_id??"",
            'addableToBulkInvoice'=>$this->addable_to_bulk_invoice,
            'allowedDaysToPay'=>$this->allowed_days_to_pay??0,
            'isCompany'=>$this->is_company??0,
            'iban' => $this->iban??"",
            'abi'=> $this->abi??"",
            'cab' => $this->cab??"",
            'totalTax' => $this->total_tax??0,
            'hasRecurringInvoice' => $this->has_recurring_invoice,
            'totalTaxDescription' => $this->total_tax_description??"",
            'addresses' => AllClientAddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => AllClientContactResource::collection($this->whenLoaded('contacts')),
            'payInstallments' => AllPayInstallmentResource::collection($this->whenLoaded('payInstallments')),
            'sdi' => $this->sdi??"",
            'proforma' => $this->proforma??false,
            'limitDecreto' => $this->limit_decreto??0,
        ];
    }
}
