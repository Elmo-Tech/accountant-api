<?php

namespace App\Http\Resources\Client\ClientAddress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //dd($this->countries->toArray());
        return [
            'clientAddressId' => $this->id,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'cap' => $this->cap,
            'region' => $this->region,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'note' => $this->note,
            'parameterValueId' => $this->parameter_value_id??"",
            'clientId' => $this->client_id
        ];

    }
}
