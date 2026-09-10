<?php

namespace Tests\Unit;

use App\Models\Invoice\Invoice;
use App\Services\Invoice\InvoiceListService;
use App\Services\Invoice\InvoiceTotalsService;
use PHPUnit\Framework\TestCase;

class InvoiceTotalsTest extends TestCase
{
    public function test_filtered_totals_sum_rounded_rows_without_recalculating_iva(): void
    {
        $calculator = new InvoiceTotalsService;
        $rows = array_fill(0, 3, ['taxableAmount' => 0.01, 'ivaAmount' => 0.0, 'total' => 0.01]);
        $this->assertSame([
            'taxableAmount' => 0.03, 'ivaAmount' => 0.0, 'totalAmount' => 0.03,
        ], $calculator->summarize($rows, 'total'));
        $this->assertSame([
            'taxableAmount' => 0.0, 'ivaAmount' => 0.0, 'totalAmount' => 0.0,
        ], $calculator->summarize([], 'total'));
    }

    public function test_fixed_discount_reduces_taxable_base_and_excludes_expenses(): void
    {
        $totals = (new InvoiceTotalsService)->calculate(100, 10, 4, 0, 0, 20);
        $this->assertSame(84.0, $totals['taxableAmount']);
        $this->assertSame(18.48, $totals['ivaAmount']);
        $this->assertSame(112.48, $totals['total']);
    }

    public function test_percentage_discount_and_client_surcharge_cap(): void
    {
        $totals = (new InvoiceTotalsService)->calculate(1000, 80, 4, 30, '1', 10);
        $this->assertSame(30.0, $totals['additionalAmount']);
        $this->assertSame(103.0, $totals['discountAmount']);
        $this->assertSame(927.0, $totals['taxableAmount']);
        $this->assertSame(203.94, $totals['ivaAmount']);
        $this->assertSame(2.0, $totals['stampAmount']);
        $this->assertSame(1212.94, $totals['total']);
    }

    public function test_iva_is_rounded_on_total_and_stamp_threshold_is_strict(): void
    {
        $calculator = new InvoiceTotalsService;
        $this->assertSame(0.01, $calculator->calculate(0.03)['ivaAmount']);
        $this->assertSame(0.0, $calculator->calculate(0, 77.47)['stampAmount']);
        $this->assertSame(2.0, $calculator->calculate(0, 77.48)['stampAmount']);
        $this->assertSame(0.0, $calculator->calculate(0, 100)['ivaAmount']);
        $this->assertSame(0.0, $calculator->calculate(0)['total']);
    }

    public function test_discount_cannot_make_taxable_base_negative(): void
    {
        $totals = (new InvoiceTotalsService)->calculate(10, 5, 0, 0, 0, 100);
        $this->assertSame(0.0, $totals['taxableAmount']);
        $this->assertSame(5.0, $totals['total']);
    }

    public function test_invoice_numbers_sort_numerically_with_missing_numbers_last(): void
    {
        $invoices = collect([
            ['id' => 4, 'invoice_xml_number' => null, 'number' => 'IN_00004'],
            ['id' => 2, 'invoice_xml_number' => '1/10'],
            ['id' => 3, 'invoice_xml_number' => '1/2'],
            ['id' => 1, 'invoice_xml_number' => '1/1'],
            ['id' => 5, 'invoice_xml_number' => '', 'number' => 'IN_00005'],
        ])->map(fn ($data) => (new Invoice)->forceFill($data));

        $ordered = (new InvoiceListService(new InvoiceTotalsService))->ordered($invoices);
        $this->assertSame([1, 3, 2, 4, 5], $ordered->pluck('id')->all());
        $descending = (new InvoiceListService(new InvoiceTotalsService))->ordered($invoices, 'desc');
        $this->assertSame([2, 3, 1, 5, 4], $descending->pluck('id')->all());
    }
}
