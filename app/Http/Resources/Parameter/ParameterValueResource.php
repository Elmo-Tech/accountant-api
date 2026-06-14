<?php

namespace App\Http\Resources\Parameter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'parameterValueId' => $this->id,
            'parameterValue' => $this->parameter_value,
            'description' => $this->description??"",
            'isDefault' => $this->is_default,
            'code' => $this->code??"",
            'descriptionTwo' => $this->description2??"",
            'descriptionThree' => $this->description3??""
        ];
    }
}
