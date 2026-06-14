<?php

namespace App\Http\Resources\Client\PayInstallment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayInstallmentSubDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'payInstallmentSubDataId' => $this->id,
            'price' => $this->price,
            'parameterValueId' => $this->parameter_value_id??'',
        ];
    }
}
