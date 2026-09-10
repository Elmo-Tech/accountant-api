<?php

namespace App\Services\Invoice;

use App\Models\Client\ClientPayInstallment;
use App\Models\Client\ClientPayInstallmentSubData;
use App\Models\Invoice\Invoice;
use App\Models\Task\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class InvoiceListService
{
    public function __construct(private InvoiceTotalsService $totals) {}

    public function query(array $filters = []): Builder
    {
        // Resolve one date per invoice, so filtering cannot discard individual lines.
        $installmentDate = '(SELECT MIN(cpi.start_at) FROM invoice_details AS date_details '
            .'INNER JOIN client_pay_installments AS cpi ON cpi.id = date_details.invoiceable_id '
            .'WHERE date_details.invoice_id = invoices.id AND date_details.invoiceable_type = ? '
            .'AND date_details.deleted_at IS NULL AND cpi.deleted_at IS NULL)';
        $date = "COALESCE(invoices.start_date, {$installmentDate}, invoices.created_at)";

        $query = Invoice::query()->with(['client', 'invoiceDetails'])
            ->whereHas('client')->whereHas('invoiceDetails');

        if (isset($filters['clientId'])) {
            $query->where('client_id', $filters['clientId']);
        }
        if (isset($filters['payStatus'])) {
            $query->where('pay_status', $filters['payStatus']);
        }
        if (isset($filters['startAt'])) {
            $query->whereRaw("DATE({$date}) >= ?", [ClientPayInstallment::class, Carbon::parse($filters['startAt'])->format('Y-m-d')]);
        }
        if (isset($filters['endAt'])) {
            $query->whereRaw("DATE({$date}) <= ?", [ClientPayInstallment::class, Carbon::parse($filters['endAt'])->format('Y-m-d')]);
        }
        if (isset($filters['hasXmlNumber'])) {
            if ((int) $filters['hasXmlNumber'] === 1) {
                $query->whereNotNull('invoice_xml_number')->whereRaw("TRIM(invoice_xml_number) <> ''");
            } else {
                $query->where(fn (Builder $q) => $q->whereNull('invoice_xml_number')->orWhereRaw("TRIM(invoice_xml_number) = ''"));
            }
        }
        if (isset($filters['hasProforma'])) {
            $query->whereHas('client', function (Builder $q) use ($filters) {
                if ((int) $filters['hasProforma'] === 1) {
                    $q->where('proforma', 1);
                } else {
                    $q->where(fn (Builder $q) => $q->where('proforma', 0)->orWhereNull('proforma'));
                }
            });
        }

        return $query;
    }

    public function ordered(Collection $invoices, string $direction = 'asc'): Collection
    {
        $multiplier = $direction === 'desc' ? -1 : 1;

        return $invoices->sort(function (Invoice $left, Invoice $right) use ($multiplier) {
            $leftNumber = trim((string) $left->invoice_xml_number);
            $rightNumber = trim((string) $right->invoice_xml_number);

            return (($leftNumber === '') <=> ($rightNumber === ''))
                ?: $multiplier * (strnatcmp($leftNumber, $rightNumber)
                    ?: strnatcmp((string) $left->number, (string) $right->number)
                    ?: ($left->id <=> $right->id));
        })->values();
    }

    public function loadSources(Collection $invoices): void
    {
        $invoices->loadMissing(['invoiceDetails.invoiceable' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                Task::class => ['serviceCategory'],
                ClientPayInstallment::class => ['parameterValue'],
                ClientPayInstallmentSubData::class => ['parameterValue'],
            ]);
        }]);
    }

    public function invoiceDate(Invoice $invoice): string
    {
        $installmentDate = $invoice->invoiceDetails
            ->where('invoiceable_type', ClientPayInstallment::class)
            ->map(fn ($detail) => $detail->invoiceable?->start_at)->filter()->sort()->first();

        return Carbon::parse($invoice->start_date ?? $installmentDate ?? $invoice->created_at)->format('Y-m-d');
    }

    public function format(Invoice $invoice): array
    {
        $amounts = $this->totals->forInvoice($invoice);

        return [
            'invoiceId' => $invoice->id,
            'invoiceNumber' => $invoice->number ?? '',
            'invoiceXmlNumber' => $invoice->invoice_xml_number ?? '',
            'invoiceDate' => $this->invoiceDate($invoice),
            'clientId' => $invoice->client_id,
            'clientName' => $invoice->client?->ragione_sociale ?? '',
            'clientAddableToBulkInvoice' => $invoice->client?->getRawOriginal('addable_to_bulk_invoice') ?? '',
            'tasks' => $invoice->invoiceDetails->map(function ($detail) {
                $source = $detail->invoiceable;
                $task = $source instanceof Task ? $source : null;
                $description = $detail->description ?: ($task?->serviceCategory?->name ?? $source?->parameterValue?->description ?? '');

                return [
                    'taskId' => $detail->id,
                    'taskTitle' => $task?->title ?? '',
                    'taskNumber' => $task?->number ?? '',
                    'serviceCategoryName' => $description,
                    'description' => $description,
                    'price' => (float) $detail->price,
                    'priceAfterDiscount' => (float) $detail->price_after_discount,
                    'extraPrice' => (float) $detail->extra_price,
                ];
            })->values()->all(),
            'totalPrice' => (float) $invoice->invoiceDetails->sum('price'),
            'totalPriceAfterDiscount' => $amounts['subtotal'],
            'totalCosts' => $amounts['extraTotal'],
            'additionalTax' => (float) ($invoice->client?->total_tax ?? 0),
            'totalAfterAdditionalTax' => $amounts['totalBeforeDiscount'],
            'invoiceDiscount' => $invoice->discount_amount ?? 0,
            'totalInvoiceAfterDiscount' => $amounts['total'],
            'taxableAmount' => $amounts['taxableAmount'],
            'ivaAmount' => $amounts['ivaAmount'],
        ];
    }
}
