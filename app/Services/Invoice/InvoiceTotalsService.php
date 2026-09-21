<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;

class InvoiceTotalsService
{
    /** Sum the rounded amounts of all filtered rows before pagination. */
    public function summarize(iterable $rows, string $totalKey): array
    {
        $cents = ['taxableAmount' => 0, 'ivaAmount' => 0, 'totalAmount' => 0];

        foreach ($rows as $row) {
            $cents['taxableAmount'] += (int) round($row['taxableAmount'] * 100);
            $cents['ivaAmount'] += (int) round($row['ivaAmount'] * 100);
            $cents['totalAmount'] += (int) round($row[$totalKey] * 100);
        }

        return array_map(fn (int $amount): float => $amount / 100, $cents);
    }

    public function forInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['client', 'invoiceDetails']);

        return $this->calculate(
            (float) $invoice->invoiceDetails->sum('price_after_discount'),
            (float) $invoice->invoiceDetails->sum('extra_price'),
            (float) ($invoice->client?->total_tax ?? 0),
            (float) ($invoice->client?->limit_decreto ?? 0),
            $invoice->discount_type,
            (float) ($invoice->discount_amount ?? 0),
        );
    }

    public function calculate(
        float $subtotal,
        float $extraTotal = 0,
        float $additionalRate = 0,
        float $additionalLimit = 0,
        int|string|null $discountType = null,
        float $discountAmount = 0,
    ): array {
        // Match legacy calculation order: retain precision through surcharge,
        // discount and IVA; round only the returned monetary values.
        $additionalAmount = $subtotal * $additionalRate / 100;
        if ($additionalLimit > 0) {
            $additionalAmount = min($additionalAmount, $additionalLimit);
        }

        $taxableBeforeDiscount = $subtotal + $additionalAmount;
        $discountBase = $taxableBeforeDiscount + $extraTotal;
        // API contract: 0 = percentage, 1 = fixed amount.
        // Legacy percentage base includes extras, before IVA and stamp.
        // The resulting discount reduces the taxable base; extras remain VAT-exempt.
        $discount = match ((string) $discountType) {
            '0' => $discountBase * $discountAmount / 100,
            '1' => $discountAmount,
            default => 0,
        };
        $discount = min(max(0, $discount), max(0, $taxableBeforeDiscount));
        $taxableAmount = $taxableBeforeDiscount - $discount;
        $ivaAmount = $taxableAmount * 0.22;
        // Preserve the stamp rule already used by PDF/XML exports.
        $stampAmount = $extraTotal > 77.47 ? 2.0 : 0.0;
        $netTotal = $taxableAmount + $extraTotal;

        return [
            'subtotal' => round($subtotal, 2),
            'extraTotal' => round($extraTotal, 2),
            'additionalAmount' => round($additionalAmount, 2),
            'discountAmount' => round($discount, 2),
            'taxableAmount' => round($taxableAmount, 2),
            'ivaAmount' => round($ivaAmount, 2),
            'netTotal' => round($netTotal, 2),
            'totalWithTax' => round($netTotal + $ivaAmount, 2),
            'stampAmount' => $stampAmount,
            'total' => round($netTotal + $ivaAmount + $stampAmount, 2),
            'totalBeforeDiscount' => round($taxableBeforeDiscount * 1.22 + $extraTotal + $stampAmount, 2),
        ];
    }
}
