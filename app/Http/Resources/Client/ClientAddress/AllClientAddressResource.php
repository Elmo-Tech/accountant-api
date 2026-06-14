<?php

namespace App\Http\Resources\Client\ClientAddress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllClientAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {


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
            'parameterValueName' => $this->parameter? $this->parameter->parameter_value : ""
        ];
    }
}
