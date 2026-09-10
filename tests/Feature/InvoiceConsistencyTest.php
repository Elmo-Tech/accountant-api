<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Private\Reports\InvoiceReportExportController;
use App\Models\Client\ClientPayInstallment;
use App\Models\Client\ClientPayInstallmentSubData;
use App\Models\Invoice\Invoice;
use App\Models\Task\Task;
use App\Services\Invoice\InvoiceDocumentService;
use App\Services\Invoice\InvoiceListService;
use App\Services\Invoice\InvoiceReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class InvoiceConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // The repository's legacy migrations do not contain every deployed column.
        // Use an isolated, explicit invoice schema; never connect to a business database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'clients' => 'id INTEGER PRIMARY KEY, ragione_sociale TEXT, total_tax REAL DEFAULT 0, limit_decreto REAL DEFAULT 0, total_tax_description TEXT, addable_to_bulk_invoice INTEGER DEFAULT 0, proforma INTEGER DEFAULT 0, deleted_at TEXT',
            'invoices' => 'id INTEGER PRIMARY KEY, client_id INTEGER, number TEXT, invoice_xml_number TEXT, start_date TEXT, end_at TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, pay_status INTEGER DEFAULT 0, pay_date TEXT, discount_type INTEGER, discount_amount REAL DEFAULT 0, payment_type_id INTEGER, bank_account_id INTEGER',
            'invoice_details' => 'id INTEGER PRIMARY KEY, invoice_id INTEGER, invoiceable_type TEXT, invoiceable_id INTEGER, price REAL DEFAULT 0, price_after_discount REAL DEFAULT 0, extra_price REAL DEFAULT 0, description TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'client_pay_installments' => 'id INTEGER PRIMARY KEY, client_id INTEGER, start_at TEXT, end_at TEXT, amount REAL, parameter_value_id INTEGER, deleted_at TEXT',
            'client_pay_installment_sub_data' => 'id INTEGER PRIMARY KEY, client_pay_installment_id INTEGER, price REAL, parameter_value_id INTEGER, deleted_at TEXT',
            'tasks' => 'id INTEGER PRIMARY KEY, service_category_id INTEGER, client_id INTEGER, invoice_id INTEGER, title TEXT, number TEXT, status INTEGER, is_new INTEGER, created_at TEXT, price REAL, price_after_discount REAL, deleted_at TEXT',
            'service_categories' => 'id INTEGER PRIMARY KEY, name TEXT, code TEXT, price REAL, add_to_invoice INTEGER, extra_is_pricable INTEGER, extra_price REAL, extra_code TEXT, extra_price_description TEXT, deleted_at TEXT',
            'client_service_discounts' => 'id INTEGER PRIMARY KEY, client_id INTEGER, service_category_ids TEXT, is_active INTEGER, deleted_at TEXT',
            'parameter_values' => 'id INTEGER PRIMARY KEY, parameter_id INTEGER, parameter_order INTEGER, parameter_value TEXT, description TEXT, description2 TEXT, description3 TEXT, code TEXT, is_default INTEGER DEFAULT 0, deleted_at TEXT',
            'client_addresses' => 'id INTEGER PRIMARY KEY, client_id INTEGER, address TEXT, deleted_at TEXT',
            'client_bank_accounts' => 'id INTEGER PRIMARY KEY, client_id INTEGER, is_main INTEGER, deleted_at TEXT',
        ] as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }
        DB::table('clients')->insert(['id' => 1, 'ragione_sociale' => 'Example client']);
        DB::connection()->getPdo()->sqliteCreateFunction('FIND_IN_SET', fn ($value, $list) => in_array((string) $value, explode(',', (string) $list), true) ? 1 : 0, 2);
        Storage::fake('public');
    }

    private function invoice(int $id, ?string $number = '1/1', array $attributes = []): Invoice
    {
        DB::table('invoices')->insert(array_merge([
            'id' => $id, 'client_id' => 1, 'number' => 'IN_'.str_pad($id, 5, '0', STR_PAD_LEFT),
            'invoice_xml_number' => $number, 'created_at' => '2025-01-01 00:00:00', 'end_at' => '2026-02-28',
        ], $attributes));

        return Invoice::withTrashed()->findOrFail($id);
    }

    private function detail(int $invoiceId, float $amount, array $attributes = []): void
    {
        DB::table('invoice_details')->insert(array_merge([
            'invoice_id' => $invoiceId, 'price' => $amount, 'price_after_discount' => $amount,
            'description' => 'Stored service', 'created_at' => '2025-01-01 00:00:00',
        ], $attributes));
    }

    public function test_list_orders_before_pagination_and_exposes_tax_amounts(): void
    {
        $this->invoice(1, '1/10');
        $this->invoice(2, '1/2');
        $this->invoice(3, null);
        $this->detail(1, 100);
        $this->detail(2, 200);
        $this->detail(3, 300);

        $this->getJson('/api/v1/invoices?filter[unassigned]=0&pageSize=1')
            ->assertOk()->assertJsonPath('result.invoices.0.invoiceId', 2)
            ->assertJsonPath('result.invoices.0.taxableAmount', 200)
            ->assertJsonPath('result.invoices.0.ivaAmount', 44)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('totals.taxableAmount', 600)
            ->assertJsonPath('totals.ivaAmount', 132)
            ->assertJsonPath('totals.totalAmount', 732);
        $this->getJson('/api/v1/invoices?filter[unassigned]=0&pageSize=1&page=2')
            ->assertOk()->assertJsonPath('result.invoices.0.invoiceId', 1)
            ->assertJsonPath('totals.totalAmount', 732);
        $this->getJson('/api/v1/invoices?filter[unassigned]=0&pageSize=1&page=10')
            ->assertOk()->assertJsonCount(0, 'result.invoices')
            ->assertJsonPath('totals.totalAmount', 732);
    }

    public function test_date_filter_keeps_all_invoice_lines_and_ignores_deleted_lines(): void
    {
        DB::table('client_pay_installments')->insert(['id' => 1, 'client_id' => 1, 'start_at' => '2026-02-01', 'amount' => 100]);
        $invoice = $this->invoice(1);
        $this->detail(1, 100, ['invoiceable_type' => ClientPayInstallment::class, 'invoiceable_id' => 1]);
        $this->detail(1, 50); // No installment date: formerly discarded by the joined date filter.
        $this->detail(1, 999, ['deleted_at' => '2026-02-02']);

        $this->getJson('/api/v1/invoices?filter[unassigned]=0&filter[startAt]=2026-02-01&filter[endAt]=2026-02-28')
            ->assertOk()->assertJsonCount(2, 'result.invoices.0.tasks')
            ->assertJsonPath('result.invoices.0.taxableAmount', 150)
            ->assertJsonPath('result.invoices.0.ivaAmount', 33)
            ->assertJsonPath('totals.totalAmount', 183)
            ->assertJsonPath('result.invoices.0.invoiceDate', '2026-02-01');
        $document = app(InvoiceDocumentService::class)->build($invoice);
        $this->assertSame(150.0, $document['invoiceTaxableTotal']);
        $this->assertSame(183.0, $document['invoiceTotalWithTax']);
    }

    public function test_assigned_invoice_sort_direction_is_validated_and_applied_before_pagination(): void
    {
        foreach ([1 => '1/2', 2 => '1/10', 3 => null] as $id => $number) {
            $this->invoice($id, $number);
            $this->detail($id, 100);
        }
        $this->getJson('/api/v1/invoices?filter[unassigned]=0&sortXmlNumber=asc&pageSize=1')
            ->assertOk()->assertJsonPath('result.invoices.0.invoiceId', 1)
            ->assertJsonPath('totals.totalAmount', 366);
        foreach ([1 => 2, 2 => 1, 3 => 3] as $page => $id) {
            $this->getJson('/api/v1/invoices?filter[unassigned]=0&sortXmlNumber=desc&pageSize=1&page='.$page)
                ->assertOk()->assertJsonPath('result.invoices.0.invoiceId', $id)
                ->assertJsonPath('totals.totalAmount', 366);
        }
        $this->getJson('/api/v1/invoices?filter[unassigned]=0&sortXmlNumber=invalid')
            ->assertUnprocessable()->assertJsonValidationErrors('sortXmlNumber');
    }

    public function test_stored_extra_and_discount_match_api_document_and_income_stats(): void
    {
        DB::table('clients')->where('id', 1)->update(['total_tax' => 4, 'limit_decreto' => 3]);
        DB::table('service_categories')->insert(['id' => 1, 'name' => 'Changed service', 'extra_is_pricable' => 1, 'extra_price' => 999]);
        DB::table('tasks')->insert(['id' => 1, 'service_category_id' => 1]);
        $invoice = $this->invoice(1, '1/1', ['discount_type' => 0, 'discount_amount' => 20, 'pay_status' => 1, 'pay_date' => '2026-02-20']);
        $this->detail(1, 100, ['invoiceable_type' => Task::class, 'invoiceable_id' => 1, 'extra_price' => 80]);

        $this->getJson('/api/v1/invoices?filter[unassigned]=0')->assertOk()
            ->assertJsonPath('result.invoices.0.taxableAmount', 83)
            ->assertJsonPath('result.invoices.0.ivaAmount', 18.26)
            ->assertJsonPath('result.invoices.0.totalInvoiceAfterDiscount', 183.26);
        $document = app(InvoiceDocumentService::class)->build($invoice);
        $this->assertSame(80.0, $document['invoiceItems'][1]['priceAfterDiscount']);
        $this->assertSame(183.26, $document['invoiceTotalWithTax'] + $document['stampAmount']);
        $this->getJson('/api/v1/invoice-income-stats?year=2026')->assertOk()
            ->assertJsonPath('totalAmountCollected', 183.26)->assertJsonPath('totalInvoicesAmount', 183.26);
        $this->getJson('/api/v1/invoice-income-items?type=1&year=2026')->assertOk()
            ->assertJsonPath('result.invoices.0.total', 183.26);

        $document['invoice'] = $invoice;
        $document['client'] = $invoice->client;
        app(InvoiceReportExportController::class)->generateSimpleXlsx($document);
        $files = Storage::disk('public')->files('exportedInvoices');
        $sheet = IOFactory::load(Storage::disk('public')->path($files[0]))->getActiveSheet();
        $this->assertEquals(183.26, $sheet->getCell('F3')->getValue());
        $this->assertEquals(-24.4, $sheet->getCell('C5')->getValue());
        $sum = 0;
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $sum += (float) $sheet->getCell('C'.$row)->getValue();
        }
        $this->assertEqualsWithDelta(183.26, $sum, 0.00001);
    }

    public function test_missing_source_does_not_remove_stored_invoice_amount(): void
    {
        $invoice = $this->invoice(1);
        $this->detail(1, 30, ['invoiceable_type' => ClientPayInstallmentSubData::class, 'invoiceable_id' => 999]);
        $document = app(InvoiceDocumentService::class)->build($invoice);
        $this->assertSame(30.0, $document['invoiceTaxableTotal']);
        $this->assertSame('Stored service', $document['invoiceItems'][0]['description']);
    }

    public function test_reconciliation_identifies_missing_links_amount_changes_and_extra_services(): void
    {
        $this->invoice(1, null);
        $this->detail(1, 90, ['invoiceable_type' => ClientPayInstallment::class, 'invoiceable_id' => 1]);
        $this->detail(1, 30, ['invoiceable_type' => Task::class, 'invoiceable_id' => 1]);
        $scheduled = collect([
            ['source_type' => ClientPayInstallment::class, 'source_id' => 1, 'ragione_sociale' => 'Example client', 'description' => 'Plan', 'amount' => 100],
            ['source_type' => ClientPayInstallment::class, 'source_id' => 2, 'ragione_sociale' => 'Example client', 'description' => 'Unlinked', 'amount' => 50],
        ]);
        $rows = app(InvoiceReconciliationService::class)->compare($scheduled, app(InvoiceListService::class)->query()->get());
        $this->assertSame(-10.0, $rows[0][5]);
        $this->assertStringContainsString('Importo diverso', $rows[0][9]);
        $this->assertStringContainsString('Numero XML assente', $rows[0][9]);
        $this->assertStringContainsString('Nessuna fattura', $rows[1][9]);
        $this->assertStringContainsString('Servizio aggiuntivo', $rows[2][9]);
    }

    public function test_payment_workbook_filters_dates_includes_invoices_and_preserves_negative_rows(): void
    {
        DB::table('parameter_values')->insert(['id' => 1, 'parameter_id' => 8, 'description' => 'Plan', 'parameter_value' => 'Plan']);
        DB::table('client_pay_installments')->insert([
            ['id' => 1, 'client_id' => 1, 'start_at' => '2026-02-01', 'end_at' => '2026-02-28', 'amount' => 100, 'parameter_value_id' => 1],
            ['id' => 2, 'client_id' => 1, 'start_at' => '2026-01-01', 'end_at' => '2026-01-31', 'amount' => 999, 'parameter_value_id' => 1],
        ]);
        DB::table('client_pay_installment_sub_data')->insert(['id' => 1, 'client_pay_installment_id' => 1, 'price' => -10, 'parameter_value_id' => 1]);
        $this->invoice(1);
        $this->detail(1, 100, ['invoiceable_type' => ClientPayInstallment::class, 'invoiceable_id' => 1]);
        $this->detail(1, -10, ['invoiceable_type' => ClientPayInstallmentSubData::class, 'invoiceable_id' => 1]);
        $this->detail(1, 50, ['invoiceable_type' => Task::class, 'invoiceable_id' => 999]);

        $this->getJson('/api/v1/export-client-payment?clientId=1&startAt=2026-02-01&endAt=2026-02-28')->assertOk();
        $files = Storage::disk('public')->files('exports');
        $book = IOFactory::load(Storage::disk('public')->path($files[0]));
        $this->assertEquals(90, $book->getSheetByName('Dettaglio')->getCell('D4')->getCalculatedValue());
        $this->assertEquals(90, $book->getSheetByName('Macro_Servizi')->getCell('C2')->getValue());
        $this->assertEquals(140, $book->getSheetByName('Fatture')->getCell('E2')->getValue());
        $this->assertEquals(170.8, $book->getSheetByName('Fatture')->getCell('I2')->getValue());
        $this->assertStringContainsString('Servizio aggiuntivo', $book->getSheetByName('Riconciliazione')->getCell('J4')->getValue());
    }

    public function test_empty_payment_workbook_has_zero_totals(): void
    {
        $this->getJson('/api/v1/export-client-payment')->assertOk();
        $files = Storage::disk('public')->files('exports');
        $book = IOFactory::load(Storage::disk('public')->path($files[0]));
        $this->assertEquals(0, $book->getSheetByName('Dettaglio')->getCell('D2')->getCalculatedValue());
        $this->assertEquals(0, $book->getSheetByName('Riepilogo')->getCell('B2')->getCalculatedValue());
    }

    public function test_xml_line_sums_and_payment_match_the_shared_totals(): void
    {
        Storage::fake('local');
        $invoice = $this->invoice(1, '1/1', ['discount_type' => 0, 'discount_amount' => 20]);
        $this->detail(1, 100, ['extra_price' => 80]);
        $this->detail(1, -10);
        $document = app(InvoiceDocumentService::class)->build($invoice);
        $this->assertSame(-20.0, $document['invoiceItems'][3]['priceAfterDiscount']);
        $data = array_merge($document, [
            'invoice' => $invoice, 'client' => $invoice->client,
            'clientAddressData' => ['address' => 'Example street', 'city' => 'Example city'],
            'paymentMethod' => 'MP05', 'bankAccount' => [],
        ]);
        $response = app(InvoiceReportExportController::class)->generateInvoiceXml($data);
        $xml = simplexml_load_string($response->getData(true)['data']['content']);
        $taxableLines = $xml->xpath('//DettaglioLinee[AliquotaIVA="22.00"]/PrezzoTotale');
        $sum = array_sum(array_map(fn ($line) => (float) $line, $taxableLines));
        $this->assertSame(70.0, $sum);
        $this->assertSame('70.00', (string) $xml->xpath('//DatiRiepilogo[AliquotaIVA="22.00"]/ImponibileImporto')[0]);
        $this->assertSame('15.40', (string) $xml->xpath('//DatiRiepilogo[AliquotaIVA="22.00"]/Imposta')[0]);
        $this->assertSame('167.40', (string) $xml->xpath('//ImportoTotaleDocumento')[0]);
        $this->assertSame('167.40', (string) $xml->xpath('//ImportoPagamento')[0]);
    }

    public function test_filters_exclude_deleted_records_and_handle_empty_xml_numbers(): void
    {
        DB::table('clients')->insert(['id' => 2, 'ragione_sociale' => 'Other client', 'proforma' => 1]);
        $this->invoice(1, '');
        $this->invoice(2, '1/2', ['client_id' => 2, 'pay_status' => 1, 'start_date' => '2026-03-01']);
        $this->invoice(3, '1/3', ['deleted_at' => '2026-03-02']);
        foreach ([1, 2, 3] as $id) {
            $this->detail($id, 100);
        }
        $this->getJson('/api/v1/invoices?filter[hasXmlNumber]=0')->assertOk()
            ->assertJsonCount(1, 'result.invoices')->assertJsonPath('result.invoices.0.invoiceId', 1);
        $this->getJson('/api/v1/invoices?filter[clientId]=2&filter[payStatus]=1&filter[hasProforma]=1&filter[startAt]=2026-03-01&filter[endAt]=2026-03-01')
            ->assertOk()->assertJsonCount(1, 'result.invoices')->assertJsonPath('result.invoices.0.invoiceId', 2);
        DB::table('clients')->where('id', 2)->update(['deleted_at' => '2026-03-02']);
        $this->getJson('/api/v1/invoice-income-stats')->assertOk()->assertJsonPath('totalInvoicesAmount', 122);
    }

    public function test_single_invoice_excel_rounding_matches_total_for_small_lines(): void
    {
        $invoice = $this->invoice(1);
        $this->detail(1, 0.01);
        $this->detail(1, 0.01);
        $this->detail(1, 0.01);
        $document = app(InvoiceDocumentService::class)->build($invoice);
        $document['invoice'] = $invoice;
        $document['client'] = $invoice->client;
        app(InvoiceReportExportController::class)->generateSimpleXlsx($document);
        $files = Storage::disk('public')->files('exportedInvoices');
        $sheet = IOFactory::load(Storage::disk('public')->path($files[0]))->getActiveSheet();
        $this->assertEquals(0.01, $sheet->getCell('C5')->getValue());
        $this->assertEquals(0.04, $sheet->getCell('F3')->getValue());
    }

    public function test_income_list_totals_follow_status_dates_and_client_before_pagination(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-03-01'));
        $this->invoice(1, '1/1', ['pay_status' => 1, 'pay_date' => '2026-02-10']);
        $this->invoice(2, '1/2', ['pay_status' => 1, 'pay_date' => '2026-02-20']);
        $this->invoice(3, '1/3');
        $this->invoice(4, '1/4', ['end_at' => '2026-04-30']);
        foreach ([1 => 100, 2 => 200, 3 => 300, 4 => 400] as $id => $amount) {
            $this->detail($id, $amount);
        }
        DB::table('clients')->insert(['id' => 2, 'ragione_sociale' => 'Other client']);
        $this->invoice(5, '1/5', ['client_id' => 2, 'pay_status' => 1, 'pay_date' => '2026-02-10']);
        $this->detail(5, 999);

        foreach ([1, 2] as $page) {
            $this->getJson('/api/v1/invoice-income-items?type=1&clientId=1&startDate=2026-02-01&endDate=2026-02-28&pageSize=1&page='.$page)
                ->assertOk()->assertJsonCount(1, 'result.invoices')
                ->assertJsonPath('totals.taxableAmount', 300)
                ->assertJsonPath('totals.ivaAmount', 66)
                ->assertJsonPath('totals.totalAmount', 366);
        }
        $this->getJson('/api/v1/invoice-income-items?type=2&clientId=1&year=2026&pageSize=1')
            ->assertOk()->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('totals.totalAmount', 366);
        $this->getJson('/api/v1/invoice-income-items?type=0&clientId=1&year=2026&pageSize=1')
            ->assertOk()->assertJsonPath('pagination.total', 4)
            ->assertJsonPath('totals.totalAmount', 1220);
        $this->travelBack();
    }

    public function test_filtered_empty_lists_return_zero_totals(): void
    {
        $this->invoice(1);
        $this->detail(1, 100);
        foreach ([
            '/api/v1/invoices?filter[payStatus]=1',
            '/api/v1/invoices?filter[unassigned]=1',
            '/api/v1/invoice-income-items?type=1',
        ] as $url) {
            $this->getJson($url)->assertOk()->assertJsonCount(0, 'result.invoices')
                ->assertJsonPath('totals.taxableAmount', 0)
                ->assertJsonPath('totals.ivaAmount', 0)
                ->assertJsonPath('totals.totalAmount', 0);
        }
    }

    public function test_unassigned_preview_totals_cover_all_client_groups_and_follow_filters(): void
    {
        DB::table('clients')->insert(['id' => 2, 'ragione_sociale' => 'Other client']);
        DB::table('service_categories')->insert(['id' => 1, 'name' => 'Service', 'price' => 100, 'add_to_invoice' => 1, 'extra_is_pricable' => 0]);
        foreach ([1, 2] as $clientId) {
            DB::table('tasks')->insert([
                'id' => $clientId, 'client_id' => $clientId, 'service_category_id' => 1,
                'status' => 2, 'is_new' => 1, 'created_at' => '2026-02-10 00:00:00',
            ]);
        }
        foreach ([1, 2] as $page) {
            $this->getJson('/api/v1/invoices?filter[unassigned]=1&pageSize=1&page='.$page)
                ->assertOk()->assertJsonCount(1, 'result.invoices')
                ->assertJsonPath('pagination.total', 2)
                ->assertJsonPath('totals.taxableAmount', 200)
                ->assertJsonPath('totals.ivaAmount', 44)
                ->assertJsonPath('totals.totalAmount', 244);
        }
        $this->getJson('/api/v1/invoices?filter[unassigned]=1&filter[clientId]=1')
            ->assertOk()->assertJsonPath('totals.totalAmount', 122);
    }
}
