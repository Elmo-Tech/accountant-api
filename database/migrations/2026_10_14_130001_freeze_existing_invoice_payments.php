<?php

use App\Models\Invoice\Invoice;
use App\Services\Invoice\InvoicePaymentService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy data has no bank history: preserve the values available at deployment.
        Invoice::withTrashed()->whereNull('payment_snapshot')
            ->whereNotNull('invoice_xml_number')->whereRaw("TRIM(invoice_xml_number) <> ''")
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    app(InvoicePaymentService::class)->forDocument($invoice);
                }
            });
    }

    public function down(): void
    {
        // Keep historical snapshots; the preceding schema migration removes the column.
    }
};
