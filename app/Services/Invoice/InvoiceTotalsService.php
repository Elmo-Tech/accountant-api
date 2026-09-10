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
        $subtotal = round($subtotal, 2);
        $extraTotal = round($extraTotal, 2);
        $additionalAmount = round($subtotal * $additionalRate / 100, 2);
        if ($additionalLimit > 0) {
            $additionalAmount = min($additionalAmount, $additionalLimit);
        }

        $taxableBeforeDiscount = round($subtotal + $additionalAmount, 2);
        // Existing API contract: 0 = fixed amount, 1 = percentage.
        // Discounts reduce the taxable base; excluded expenses retain their value.
        $discount = match ((string) $discountType) {
            '0' => $discountAmount,
            '1' => $taxableBeforeDiscount * $discountAmount / 100,
            default => 0,
        };
        $discount = round(min(max(0, $discount), max(0, $taxableBeforeDiscount)), 2);
        $taxableAmount = round($taxableBeforeDiscount - $discount, 2);
        $ivaAmount = round($taxableAmount * 0.22, 2);
        // Preserve the stamp rule already used by PDF/XML exports.
        $stampAmount = $extraTotal > 77.47 ? 2.0 : 0.0;
        $netTotal = round($taxableAmount + $extraTotal, 2);

        return [
            'subtotal' => $subtotal,
            'extraTotal' => $extraTotal,
            'additionalAmount' => $additionalAmount,
            'discountAmount' => $discount,
            'taxableAmount' => $taxableAmount,
            'ivaAmount' => $ivaAmount,
            'netTotal' => $netTotal,
            'totalWithTax' => round($netTotal + $ivaAmount, 2),
            'stampAmount' => $stampAmount,
            'total' => round($netTotal + $ivaAmount + $stampAmount, 2),
            'totalBeforeDiscount' => round($taxableBeforeDiscount * 1.22 + $extraTotal + $stampAmount, 2),
        ];
    }
}
