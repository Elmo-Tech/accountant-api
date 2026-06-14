<?php

namespace App\Http\Resources\Client\ClientBankAccount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientBankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'clientBankAccountId' => $this->id,
            'bankId' => $this->bank_id??'',
            'iban' => $this->iban??"",
            'abi'=> $this->abi??"",
            'cab' => $this->cab??"",
            'isMain' => $this->is_main,
            'clientId' => $this->client_id
        ];

    }
}
