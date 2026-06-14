<?php

namespace App\Http\Resources\ServiceCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class ServiceCategoryResource extends JsonResource
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
            'description' => $this->description??'',
            'addToInvoice' => $this->add_to_invoice,
            'serviceTypeId'=>$this->service_type_id??"",
            'price' => $this->price,
            'extraIsPricable'=>$this->extra_is_pricable??0,
            'extraPrice'=>$this->extra_price??0,
            'extraCode'=>$this->extra_code??'',
            'extraPriceDescription'=>$this->extra_price_description??''
        ];
    }
}
