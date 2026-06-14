<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoiceId' => $this->resource['invoiceId'],
            'invoiceNumber' => $this->resource['invoiceNumber'],
            'invoiceXmlNumber' => $this->resource['invoiceXmlNumber'] ?? "",
            'invoiceDate' => $this->resource['invoiceDate']??"",
            'clientId' => $this->resource['clientId'],
            'clientName' => $this->resource['clientName'],
            'tasks' => $this->resource['tasks'],
            'totalPrice' => $this->resource['totalPrice'],
            'totalPriceAfterDiscount' => $this->resource['totalPriceAfterDiscount'],
            'addableToBulkInvoice' => $this->resource['clientAddableToBulkInvoice'],
            'additionalTax' => $this->resource['additionalTax'],
            'totalAfterAdditionalTax' => $this->resource['totalAfterAdditionalTax'],
            'invoiceDiscount' => $this->resource['invoiceDiscount']??0,
            'totalInvoiceAfterDiscount' => $this->resource['totalInvoiceAfterDiscount'],
            'totalCost' => $this->resource['totalCosts'],

        ];
    }
}
