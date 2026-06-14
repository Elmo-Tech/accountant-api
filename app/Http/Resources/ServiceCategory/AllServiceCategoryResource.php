<?php

namespace App\Http\Resources\ServiceCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AllServiceCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'serviceCategoryId' => $this->id,
            'name' => $this->name,
            'code' => $this->code??"",
            'addToInvoice' => $this->add_to_invoice,
            'serviceTypeId'=>$this->service_type_id??"",
            'price' => $this->price
        ];
    }
}
