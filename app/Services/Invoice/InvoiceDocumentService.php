<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use App\Models\Task\Task;
use Carbon\Carbon;

class InvoiceDocumentService
{
    public function __construct(private InvoiceTotalsService $totals, private InvoiceListService $listing) {}

    public function build(Invoice $invoice): array
    {
        $invoice->loadMissing(['client', 'invoiceDetails']);
        $this->listing->loadSources($invoice->newCollection([$invoice]));
        $amounts = $this->totals->forInvoice($invoice);
        $items = [];

        foreach ($invoice->invoiceDetails as $detail) {
            $source = $detail->invoiceable;
            $category = $source instanceof Task ? $source->serviceCategory : null;
            $parameter = $source instanceof Task ? null : $source?->parameterValue;
            $description = $detail->description ?: ($category?->name ?? $parameter?->description ?? '');
            $price = (float) $detail->price_after_discount;
            $extra = (float) $detail->extra_price;

            // Monetary values belong to invoice_details, even if a source is edited/deleted.
            if ($price != 0 || $extra == 0 || (float) $detail->price != 0) {
                $items[] = $this->item($description, $price, 22, $category?->code ?? $parameter?->code ?? '..', $price == 0);
                $items[array_key_last($items)]['price'] = (float) $detail->price;
            }
            if ($extra != 0) {
                $items[] = $this->item($category?->extra_price_description ?? 'Spese escluse IVA', $extra, 0, $category?->extra_code ?? 'N1');
            }
        }

        if ($amounts['additionalAmount'] != 0) {
            $items[] = $this->item($invoice->client?->total_tax_description ?? '', $amounts['additionalAmount'], 22, '00000001');
        }
        if ($amounts['discountAmount'] > 0) {
            $item = $this->item('sconto', -$amounts['discountAmount'], 22);
            $item['is_discount'] = true;
            $items[] = $item;
        }

        return [
            'invoiceItems' => $items,
            'invoiceStartAt' => Carbon::parse($this->listing->invoiceDate($invoice))->format('d/m/Y'),
            'invoiceTotalTax' => $amounts['ivaAmount'],
            'invoiceTotal' => $amounts['netTotal'],
            'invoiceTaxableTotal' => $amounts['taxableAmount'],
            'invoiceTotalWithTax' => $amounts['totalWithTax'],
            'applyStamp' => $amounts['stampAmount'] > 0,
            'stampAmount' => $amounts['stampAmount'],
        ];
    }

    private function item(string $description, float $price, int $tax, string $code = '..', bool $descriptive = false): array
    {
        return [
            'description' => $description,
            'price' => $price,
            'priceAfterDiscount' => $price,
            'additionalTaxPercentage' => $tax,
            'serviceCode' => $code,
            'is_descriptive_only' => $descriptive,
            'is_discount' => false,
        ];
    }
}
