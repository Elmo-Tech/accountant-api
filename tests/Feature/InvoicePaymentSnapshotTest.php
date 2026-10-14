<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Private\Reports\InvoiceReportExportController;
use App\Models\Invoice\Invoice;
use App\Services\Invoice\InvoiceDocumentService;
use App\Services\Invoice\InvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePaymentSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'clients' => 'id INTEGER PRIMARY KEY, ragione_sociale TEXT, iva TEXT, cf TEXT, deleted_at TEXT',
            'invoices' => 'id INTEGER PRIMARY KEY, client_id INTEGER, number TEXT, invoice_xml_number TEXT, start_date TEXT, end_at TEXT, payment_type_id INTEGER, bank_account_id INTEGER, discount_type INTEGER, discount_amount REAL, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'invoice_details' => 'id INTEGER PRIMARY KEY, invoice_id INTEGER, deleted_at TEXT',
            'parameter_values' => 'id INTEGER PRIMARY KEY, parameter_id INTEGER, parameter_order INTEGER, parameter_value TEXT, code TEXT, description TEXT, description2 TEXT, description3 TEXT, is_default INTEGER DEFAULT 0, deleted_at TEXT',
            'client_bank_accounts' => 'id INTEGER PRIMARY KEY, client_id INTEGER, bank_id INTEGER, iban TEXT, abi TEXT, cab TEXT, is_main INTEGER, deleted_at TEXT',
            'client_addresses' => 'id INTEGER PRIMARY KEY, client_id INTEGER, address TEXT, city TEXT, deleted_at TEXT',
        ] as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }
        (require database_path('migrations/2026_10_14_130000_add_payment_snapshot_to_invoices.php'))->up();
        DB::table('clients')->insert(['id' => 1, 'ragione_sociale' => 'Example']);
        foreach ([
            ['id' => 20, 'parameter_id' => 4, 'parameter_order' => 4, 'parameter_value' => 'Bonifico Bancario', 'code' => 'MP05'],
            ['id' => 15, 'parameter_id' => 4, 'parameter_order' => 4, 'parameter_value' => 'RiBa', 'code' => 'MP12'],
            ['id' => 22, 'parameter_id' => 7, 'parameter_order' => 7, 'parameter_value' => 'DEFAULT_IBAN', 'description' => 'Default bank', 'is_default' => 1],
            ['id' => 114, 'parameter_id' => 7, 'parameter_order' => 7, 'parameter_value' => 'SELECTED_IBAN', 'description' => 'Selected bank'],
            ['id' => 81, 'parameter_id' => 10, 'parameter_order' => 10, 'parameter_value' => 'Client bank'],
        ] as $row) {
            DB::table('parameter_values')->insert($row);
        }
        DB::table('client_bank_accounts')->insert([
            'id' => 1, 'client_id' => 1, 'bank_id' => 81, 'iban' => 'CLIENT_IBAN',
            'abi' => '12345', 'cab' => '67890', 'is_main' => 1,
        ]);
        Storage::fake('local');
        config(['view.compiled' => Storage::disk('local')->path('views')]);
        Storage::disk('local')->makeDirectory('views');
        DB::table('client_addresses')->insert(['id' => 1, 'client_id' => 1, 'address' => 'Example street', 'city' => 'Example city']);
        $this->mock(InvoiceDocumentService::class, function ($mock) {
            $mock->shouldReceive('build')->andReturn([
                'invoiceItems' => [], 'invoiceStartAt' => '08/10/2026',
                'invoiceTotalTax' => 0, 'invoiceTotal' => 0, 'invoiceTaxableTotal' => 0,
                'invoiceTotalWithTax' => 0, 'applyStamp' => false, 'stampAmount' => 0,
            ]);
        });
    }

    private function invoice(int $id = 1, array $attributes = []): Invoice
    {
        DB::table('invoices')->insert(array_merge([
            'id' => $id, 'client_id' => 1, 'number' => 'IN_'.$id, 'payment_type_id' => 20,
            'bank_account_id' => 114, 'end_at' => '2026-10-08', 'updated_at' => '2026-10-08 10:00:00',
        ], $attributes));

        return Invoice::withTrashed()->findOrFail($id);
    }

    private function updatePayload(array $extra = []): array
    {
        return array_merge(['invoiceId' => 1, 'endAt' => '2026-10-08', 'paymentTypeId' => 20,
            'discountType' => 0, 'discountAmount' => 0], $extra);
    }

    private function documentData(int $id = 1): array
    {
        $controller = app(InvoiceReportExportController::class);
        $method = new \ReflectionMethod($controller, 'getInvoiceData');
        $method->setAccessible(true);

        return $method->invoke($controller, new Request(['invoiceIds' => [$id]]));
    }

    private function exportXml(int $id = 1): string
    {
        $response = app(InvoiceReportExportController::class)->index(new Request([
            'type' => 'xml', 'invoiceIds' => [$id],
        ]));
        $this->assertSame(200, $response->getStatusCode());

        return $response->getData(true)['data']['content'];
    }

    public function test_missing_null_or_empty_bank_does_not_clear_existing_selection(): void
    {
        $invoice = $this->invoice();
        foreach ([[], ['bankAccountId' => null], ['bankAccountId' => '']] as $extra) {
            $this->putJson('/api/v1/invoices/update', $this->updatePayload($extra))->assertOk();
            $this->assertSame(114, $invoice->fresh()->bank_account_id);
        }
        $this->putJson('/api/v1/invoices/update', $this->updatePayload(['bankAccountId' => 22]))->assertOk();
        $this->assertSame(22, $invoice->fresh()->bank_account_id);
    }

    public function test_number_button_freezes_payment_for_pdf_and_xml_and_is_repeatable(): void
    {
        $invoice = $this->invoice();
        $first = $this->postJson('/api/v1/invoices/generate-xml-number', ['invoiceId' => 1])->assertOk();
        $snapshot = $invoice->fresh()->payment_snapshot;
        $this->assertSame('SELECTED_IBAN', $snapshot['bankAccount']['iban']);
        DB::table('parameter_values')->whereIn('id', [20, 114])->update(['deleted_at' => '2026-10-14']);
        DB::table('client_bank_accounts')->update(['iban' => 'CHANGED_CLIENT_IBAN']);
        $this->postJson('/api/v1/invoices/generate-xml-number', ['invoiceId' => 1])->assertOk()
            ->assertJsonPath('invoiceXmlNumber', $first->json('invoiceXmlNumber'));
        $this->assertSame($snapshot, $invoice->fresh()->payment_snapshot);
        $html = view('invoice_pdf_report', $this->documentData())->render();
        $this->assertSame(2, substr_count($html, 'SELECTED_IBAN'));
        $this->assertStringNotContainsString('CLIENT_IBAN', $html);
        $xml = $this->exportXml();
        $this->assertStringContainsString('<IBAN>SELECTED_IBAN</IBAN>', $xml);
        $this->assertStringContainsString('<ModalitaPagamento>MP05</ModalitaPagamento>', $xml);
    }

    public function test_direct_xml_export_freezes_bank_without_requiring_client_bank_details(): void
    {
        $invoice = $this->invoice();
        DB::table('client_bank_accounts')->delete();
        $this->assertStringContainsString('<IBAN>SELECTED_IBAN</IBAN>', $this->exportXml());
        $this->assertNotEmpty($invoice->fresh()->invoice_xml_number);
        $this->assertSame('SELECTED_IBAN', $invoice->fresh()->payment_snapshot['bankAccount']['iban']);
        DB::table('parameter_values')->where('id', 114)->update(['parameter_value' => 'CHANGED_IBAN']);
        $this->assertStringContainsString('<IBAN>SELECTED_IBAN</IBAN>', $this->exportXml());
    }

    public function test_only_null_bank_uses_default_and_numbered_invoice_keeps_that_default(): void
    {
        $invoice = $this->invoice(1, ['bank_account_id' => null]);
        $payments = app(InvoicePaymentService::class);
        $this->assertSame('DEFAULT_IBAN', $payments->forDocument($invoice)['bankAccount']['iban']);
        foreach ([0, 999, 114] as $id) {
            if ($id === 114) {
                DB::table('parameter_values')->where('id', 114)->update(['deleted_at' => '2026-10-14']);
            }
            $invoice->bank_account_id = $id;
            $this->assertSame('', $payments->forDocument($invoice)['bankAccount']['iban']);
        }
        $this->postJson('/api/v1/invoices/generate-xml-number', ['invoiceId' => 1])->assertOk();
        DB::table('parameter_values')->where('id', 22)->update(['is_default' => 0]);
        DB::table('parameter_values')->where('id', 114)->update(['is_default' => 1, 'deleted_at' => null]);
        $this->assertSame('DEFAULT_IBAN', $payments->forDocument($invoice->fresh())['bankAccount']['iban']);
    }

    public function test_numbered_invoice_rejects_bank_or_method_changes_but_allows_omitted_bank(): void
    {
        $invoice = $this->invoice();
        $this->postJson('/api/v1/invoices/generate-xml-number', ['invoiceId' => 1])->assertOk();
        $this->putJson('/api/v1/invoices/update', $this->updatePayload(['bankAccountId' => 22]))
            ->assertUnprocessable()->assertJsonValidationErrors('bankAccountId');
        $this->putJson('/api/v1/invoices/update', $this->updatePayload(['paymentTypeId' => 15]))
            ->assertUnprocessable()->assertJsonValidationErrors('paymentTypeId');
        $this->putJson('/api/v1/invoices/update', $this->updatePayload())->assertOk();
        $this->assertSame(114, $invoice->fresh()->bank_account_id);
        $this->assertSame(20, $invoice->fresh()->payment_type_id);
    }

    public function test_riba_snapshot_preserves_client_bank_after_main_account_changes(): void
    {
        $invoice = $this->invoice(1, ['payment_type_id' => 15]);
        $this->postJson('/api/v1/invoices/generate-xml-number', ['invoiceId' => 1])->assertOk();
        DB::table('client_bank_accounts')->update(['deleted_at' => '2026-10-14']);
        DB::table('parameter_values')->where('id', 81)->update(['parameter_value' => 'Changed bank']);
        $data = $this->documentData();
        $this->assertSame('CLIENT_IBAN', $data['clientBankAccount']['iban']);
        $html = view('invoice_pdf_report', $data)->render();
        $this->assertStringContainsString('CLIENT_IBAN', $html);
        $this->assertStringNotContainsString('SELECTED_IBAN', $html);
        $xml = $this->exportXml();
        $this->assertStringContainsString('<IstitutoFinanziario>Client bank</IstitutoFinanziario>', $xml);
        $this->assertStringContainsString('<ABI>12345</ABI>', $xml);
        $this->assertStringContainsString('<CAB>67890</CAB>', $xml);
    }

    public function test_backfill_freezes_only_numbered_invoices_without_overwriting_history_or_timestamps(): void
    {
        $legacy = $this->invoice(1, ['invoice_xml_number' => '1/164']);
        $draft = $this->invoice(2, ['invoice_xml_number' => '']);
        $this->invoice(3, ['invoice_xml_number' => '1/165', 'deleted_at' => '2026-10-13']);
        $migration = require database_path('migrations/2026_10_14_130001_freeze_existing_invoice_payments.php');
        $migration->up();
        $snapshot = $legacy->fresh()->payment_snapshot;
        $this->assertSame('SELECTED_IBAN', $snapshot['bankAccount']['iban']);
        $this->assertSame('2026-10-08 10:00:00', $legacy->fresh()->getRawOriginal('updated_at'));
        $this->assertNull($draft->fresh()->payment_snapshot);
        $this->assertNotNull(Invoice::withTrashed()->find(3)->payment_snapshot);
        DB::table('parameter_values')->where('id', 114)->update(['parameter_value' => 'NEW_IBAN']);
        $migration->up();
        $this->assertSame($snapshot, $legacy->fresh()->payment_snapshot);
    }
}
