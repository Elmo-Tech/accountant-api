<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class SendUploadedInvoiceTest extends TestCase
{
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement('CREATE TABLE clients (id INTEGER PRIMARY KEY, cf TEXT, deleted_at TEXT)');
        DB::table('clients')->insert([
            ['id' => 1, 'cf' => 'LHDMMD97T01Z336N'],
            ['id' => 2, 'cf' => 'RSSMRA80A01H501U'],
        ]);
        Http::preventStrayRequests();
        Mail::shouldReceive('raw')->andReturnUsing(function ($body, $callback) {
            $email = new Email;
            $callback(new Message($email));
            $this->sent[] = $email;
        });
    }

    private function pdf(string $text): string
    {
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(100, 10, $text);

        return $pdf->Output('S');
    }

    private function invoice(int $page, string $cf = 'LHDMMD97T01Z336N'): array
    {
        return ['page' => $page, 'success' => true, 'cf' => $cf, 'status' => 'processed',
            'pdf_base64' => base64_encode($this->pdf('Invoice '.$page.' '.$cf))];
    }

    private function upload(array $responses, int $count = 1)
    {
        Http::fake(['*/read-cf' => Http::response($responses)]);
        $files = [];
        for ($i = 0; $i < $count; $i++) {
            $files[] = UploadedFile::fake()->createWithContent('batch.pdf', $this->pdf('Source batch '.$i));
        }

        return $this->postJson('/api/v1/send-uploaded-invoice', ['files' => $files]);
    }

    public function test_repeated_client_gets_three_separate_invoices_and_other_client_gets_its_own(): void
    {
        $invoices = [$this->invoice(1), $this->invoice(2, 'RSSMRA80A01H501U'), $this->invoice(3), $this->invoice(4)];
        $response = $this->upload([['success' => true, 'page_count' => 4, 'invoices' => $invoices]])
            ->assertOk()->assertJsonCount(4, 'results');
        $this->assertCount(4, $this->sent);
        foreach ($this->sent as $index => $email) {
            $response->assertJsonPath('results.'.$index.'.email_sent', true);
            $this->assertSame(['MOHAMEDELHADDAD997@gmail.com', 'mr10dev10@gmail.com'],
                array_map(fn ($address) => $address->getAddress(), $email->getTo()));
            $this->assertCount(1, $email->getAttachments());
            $attachment = $email->getAttachments()[0];
            $this->assertSame(base64_decode($invoices[$index]['pdf_base64']), $attachment->getBody());
            $this->assertSame('batch_file_1_page_'.($index + 1).'.pdf', $attachment->getFilename());
        }
        $this->assertStringNotContainsString('pdf_base64', $response->getContent());
    }

    public function test_missing_unknown_and_invalid_pages_are_skipped_without_stopping_valid_pages(): void
    {
        $invoices = [
            ['page' => 1, 'success' => true, 'cf' => null, 'status' => 'not_found'],
            $this->invoice(2, 'UNKNOWN'),
            array_merge($this->invoice(3), ['pdf_base64' => 'invalid']),
            ['page' => 4, 'success' => false, 'error' => 'OCR failed'],
            $this->invoice(5),
        ];
        $this->upload([['success' => true, 'page_count' => 5, 'invoices' => $invoices]])
            ->assertOk()->assertJsonPath('results.0.email_sent', false)
            ->assertJsonPath('results.1.client_found', false)
            ->assertJsonPath('results.2.success', false)
            ->assertJsonPath('results.3.email_sent', false)
            ->assertJsonPath('results.4.email_sent', true);
        $this->assertCount(1, $this->sent);
    }

    public function test_old_contract_is_rejected_before_any_email(): void
    {
        $this->upload([
            ['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1)]],
            ['success' => true, 'cf' => 'LHDMMD97T01Z336N'],
        ], 2)->assertStatus(502);
        $this->assertCount(0, $this->sent);
    }

    public function test_duplicate_page_numbers_are_rejected(): void
    {
        $this->upload([['success' => true, 'page_count' => 2, 'invoices' => [$this->invoice(1), $this->invoice(1)]]])
            ->assertStatus(502);
        $this->assertCount(0, $this->sent);
    }

    public function test_duplicate_upload_names_keep_source_results_and_attachments_distinct(): void
    {
        $this->upload([
            ['success' => false, 'error' => 'Invalid PDF', 'invoices' => []],
            ['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1)]],
            ['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1, 'RSSMRA80A01H501U')]],
        ], 3)->assertOk()->assertJsonCount(3, 'results')
            ->assertJsonPath('results.0.success', false)
            ->assertJsonPath('results.1.file_index', 1)
            ->assertJsonPath('results.2.file_index', 2);
        $this->assertCount(2, $this->sent);
        $this->assertNotSame($this->sent[0]->getAttachments()[0]->getFilename(), $this->sent[1]->getAttachments()[0]->getFilename());
    }

    public function test_mail_failure_does_not_block_later_invoices(): void
    {
        Mail::swap(\Mockery::mock());
        Mail::shouldReceive('raw')->once()->ordered()->andThrow(new \RuntimeException('SMTP failure'));
        Mail::shouldReceive('raw')->once()->ordered()->andReturnNull();
        $this->upload([['success' => true, 'page_count' => 2, 'invoices' => [$this->invoice(1), $this->invoice(2)]]])
            ->assertOk()->assertJsonPath('results.0.email_sent', false)
            ->assertJsonPath('results.0.email_error', 'SMTP failure')
            ->assertJsonPath('results.1.email_sent', true);
    }
}
