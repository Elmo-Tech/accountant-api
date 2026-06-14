<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllClientResource extends JsonResource
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
            'paymentTypeId' => $this->payment_type_id??"" ,
            'payStepsId'=> $this->pay_steps_id??"",
            'paymentTypeTwoId'=> $this->payment_type_two_id??"",
            'addableToBulkInvoice'=>$this->addable_to_bulk_invoice,
            'allowedDaysToPay'=>$this->allowed_days_to_pay??0,
            'iban' => $this->iban??"",
            'abi'=> $this->abi??"",
            'cab' => $this->cab??"",
            'limitDecreto' => $this->limit_decreto??0,
        ];
    }
}
