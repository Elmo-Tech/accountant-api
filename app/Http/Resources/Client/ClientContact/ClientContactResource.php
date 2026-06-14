<?php

namespace App\Http\Resources\Client\ClientContact;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'clientContactId' => $this->id,
            'firstName' => $this->first_name??"",
            'lastName' => $this->last_name??"",
            'phone' => $this->phone??"",
            'prefix' => $this->prefix??"",
            'email' => $this->email??"",
            'note' => $this->note??"",
            'parameterValueId' => $this->parameter_value_id??"", //TODO: check if it's needed
            'clientId' => $this->client_id,
            'cf' => $this->cf??"",
        ];

    }
}
