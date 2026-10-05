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
    private const SIGNATURE = "\n\nCordiali saluti.\nElaborazioni Srl\nVia Stazione, 9/D\nCrema (CR)\nTel.+39 0373 86998";

    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['mail.from.address' => 'billing@example.test', 'mail.from.name' => 'Laravel']);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement('CREATE TABLE clients (id INTEGER PRIMARY KEY, cf TEXT, email TEXT, deleted_at TEXT)');
        DB::statement('CREATE TABLE client_contacts (id INTEGER PRIMARY KEY, client_id INTEGER, cf TEXT, email TEXT, deleted_at TEXT)');
        DB::table('clients')->insert([
            ['id' => 1, 'cf' => 'LHDMMD97T01Z336N'],
            ['id' => 2, 'cf' => 'RSSMRA80A01H501U'],
        ]);
        DB::table('clients')->where('id', 1)->update(['email' => 'real-client@example.test']);
        DB::table('client_contacts')->insert([
            ['id' => 10, 'client_id' => 1, 'cf' => 'RNCSMN92T22I274S', 'email' => 'real-contact@example.test'],
            ['id' => 11, 'client_id' => 1, 'cf' => 'LHDMMD97T01Z336N', 'email' => 'duplicate-contact@example.test'],
        ]);
        Http::preventStrayRequests();
        Mail::shouldReceive('raw')->andReturnUsing(function ($body, $callback) {
            $email = new Email;
            $email->text($body);
            $callback(new Message($email));
            // Every test verifies that no real client/contact, Angela, or BCC
            // recipient can receive these test messages.
            $this->assertSame(['mr10dev10@gmail.com'], array_map(fn ($address) => $address->getAddress(), $email->getTo()));
            $this->assertSame([], $email->getCc());
            $this->assertSame([], $email->getBcc());
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
            'presentation_type' => 'cartacea',
            'invoice_number' => (string) (76 + $page), 'due_date' => '2026-09-30',
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

    public function test_each_client_gets_one_email_with_all_their_invoices_as_separate_attachments(): void
    {
        $invoices = [$this->invoice(1), $this->invoice(2, 'RSSMRA80A01H501U'), $this->invoice(3), $this->invoice(4)];
        $response = $this->upload([['success' => true, 'page_count' => 4, 'invoices' => $invoices]])
            ->assertOk()->assertJsonCount(4, 'results');
        $this->assertCount(2, $this->sent);
        $invoiceIndexesByEmail = [[0, 2, 3], [1]];
        foreach ($this->sent as $index => $email) {
            $this->assertSame(['mr10dev10@gmail.com'],
                array_map(fn ($address) => $address->getAddress(), $email->getTo()));
            $this->assertSame([],
                array_map(fn ($address) => $address->getAddress(), $email->getBcc()));
            $this->assertSame([], $email->getCc());
            $this->assertCount(count($invoiceIndexesByEmail[$index]), $email->getAttachments());
            $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 30/09/2026.".self::SIGNATURE, $email->getTextBody());
            $this->assertSame('Modelli F24 in scadenza - 30/09/2026', $email->getSubject());
            $this->assertSame('Servizio F24', $email->getFrom()[0]->getName());
            $this->assertSame('billing@example.test', $email->getFrom()[0]->getAddress());
            foreach ($invoiceIndexesByEmail[$index] as $attachmentIndex => $invoiceIndex) {
                $response->assertJsonPath('results.'.$invoiceIndex.'.email_sent', true);
                $response->assertJsonPath('results.'.$invoiceIndex.'.emails', ['mr10dev10@gmail.com']);
                $attachment = $email->getAttachments()[$attachmentIndex];
                $this->assertSame(base64_decode($invoices[$invoiceIndex]['pdf_base64']), $attachment->getBody());
                $this->assertSame('batch_file_1_page_'.($invoiceIndex + 1).'.pdf', $attachment->getFilename());
            }
        }
        $this->assertStringNotContainsString('pdf_base64', $response->getContent());
        $this->assertStringNotContainsString('mohamedelhaddad997@gmail.com', $response->getContent());
        $this->assertStringNotContainsString('angela@elaborazionistudio.com', $response->getContent());
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

    public function test_mail_failure_marks_all_client_invoices_and_does_not_block_other_clients(): void
    {
        Mail::swap(\Mockery::mock());
        Mail::shouldReceive('raw')->once()->ordered()->andThrow(new \RuntimeException('SMTP failure'));
        Mail::shouldReceive('raw')->once()->ordered()->andReturnNull();
        $this->upload([['success' => true, 'page_count' => 3, 'invoices' => [
            $this->invoice(1), $this->invoice(2, 'RSSMRA80A01H501U'), $this->invoice(3),
        ]]])
            ->assertOk()->assertJsonPath('results.0.email_sent', false)
            ->assertJsonPath('results.0.email_error', 'SMTP failure')
            ->assertJsonPath('results.1.email_sent', true)
            ->assertJsonPath('results.2.email_sent', false)
            ->assertJsonPath('results.2.email_error', 'SMTP failure');
    }

    public function test_same_client_across_uploaded_files_gets_one_email_with_unique_attachment_names(): void
    {
        DB::table('clients')->insert(['id' => 3, 'cf' => '01719370197']);
        $this->upload([
            ['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1, '01719370197')]],
            ['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1, ' 01719370197 ')]],
        ], 2)->assertOk()->assertJsonPath('results.0.email_sent', true)
            ->assertJsonPath('results.1.email_sent', true)
            ->assertJsonPath('results.1.cf', '01719370197');
        $this->assertCount(1, $this->sent);
        $attachments = $this->sent[0]->getAttachments();
        $this->assertCount(2, $attachments);
        $this->assertSame('batch_file_1_page_1.pdf', $attachments[0]->getFilename());
        $this->assertSame('batch_file_2_page_1.pdf', $attachments[1]->getFilename());
    }

    public function test_five_attachments_use_only_first_invoice_date_in_email_and_keep_api_dates(): void
    {
        $dates = ['2026-09-30', '2026-10-31', '2026-09-01', '2026-12-01', '2027-01-01'];
        $invoices = [];
        foreach ($dates as $index => $date) {
            $invoices[] = array_merge($this->invoice($index + 1), ['due_date' => $date]);
        }
        $this->upload([['success' => true, 'page_count' => 5, 'invoices' => $invoices]])
            ->assertOk()->assertJsonPath('results.0.invoice_number', '77')
            ->assertJsonPath('results.0.due_date', '2026-09-30')
            ->assertJsonPath('results.1.due_date', '2026-10-31');
        $this->assertCount(1, $this->sent);
        $this->assertCount(5, $this->sent[0]->getAttachments());
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 30/09/2026.".self::SIGNATURE, $this->sent[0]->getTextBody());
        $this->assertSame('Modelli F24 in scadenza - 30/09/2026', $this->sent[0]->getSubject());
    }

    public function test_missing_or_invalid_reference_is_explicit_without_inventing_a_due_date(): void
    {
        $missing = $this->invoice(1);
        unset($missing['due_date'], $missing['invoice_number']);
        $invalid = array_merge($this->invoice(2), ['due_date' => '2026-02-31']);
        $valid = array_merge($this->invoice(3), ['due_date' => '2026-10-31']);
        $this->upload([['success' => true, 'page_count' => 3, 'invoices' => [$missing, $invalid, $valid]]])
            ->assertOk()->assertJsonPath('message', 'Files processed with some warnings')
            ->assertJsonPath('results.0.email_sent', true)
            ->assertJsonPath('results.0.due_date', null)
            ->assertJsonPath('results.1.due_date', null);
        $body = $this->sent[0]->getTextBody();
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24. La data di scadenza non è disponibile.".self::SIGNATURE, $body);
        $this->assertSame('Invio modelli F24', $this->sent[0]->getSubject());
        $this->assertCount(3, $this->sent[0]->getAttachments());
    }

    public function test_each_client_uses_its_own_first_date_even_across_uploads(): void
    {
        $this->upload([
            ['success' => true, 'page_count' => 2, 'invoices' => [
                $this->invoice(1),
                array_merge($this->invoice(2, 'RSSMRA80A01H501U'), ['due_date' => '2026-10-31']),
            ]],
            ['success' => true, 'page_count' => 1, 'invoices' => [
                array_merge($this->invoice(1), ['due_date' => '2026-08-01']),
            ]],
        ], 2)->assertOk();
        $this->assertCount(2, $this->sent);
        $this->assertCount(2, $this->sent[0]->getAttachments());
        $this->assertSame('Modelli F24 in scadenza - 30/09/2026', $this->sent[0]->getSubject());
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 31/10/2026.".self::SIGNATURE, $this->sent[1]->getTextBody());
    }

    public function test_contact_cf_is_found_after_client_lookup_without_using_contact_email(): void
    {
        $invoice = array_merge($this->invoice(1, 'RNCSMN92T22I274S'), [
            'presentation_type' => 'telematico_entratel', 'due_date' => '2026-10-14',
        ]);
        $this->upload([['success' => true, 'page_count' => 1, 'invoices' => [$invoice]]])
            ->assertOk()->assertJsonPath('results.0.client_found', false)
            ->assertJsonPath('results.0.contact_found', true)
            ->assertJsonPath('results.0.matched_in', 'contacts')
            ->assertJsonPath('results.0.matched_id', 10)
            ->assertJsonPath('results.0.email_sent', true);
        $this->assertCount(1, $this->sent);
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 14/10/2026.\nAttendiamo la solita autorizzazione per procedere con l’addebito telematico.".self::SIGNATURE, $this->sent[0]->getTextBody());
    }

    public function test_client_takes_precedence_over_contact_with_same_cf(): void
    {
        $this->upload([['success' => true, 'page_count' => 1, 'invoices' => [$this->invoice(1)]]])
            ->assertOk()->assertJsonPath('results.0.client_found', true)
            ->assertJsonPath('results.0.contact_found', false)
            ->assertJsonPath('results.0.matched_in', 'clients')
            ->assertJsonPath('results.0.matched_id', 1);
        $this->assertCount(1, $this->sent);
    }

    public function test_two_paper_and_two_electronic_invoices_make_two_emails_with_two_attachments(): void
    {
        $invoices = [];
        foreach (['cartacea', 'telematico_entratel', 'cartacea', 'telematico_entratel'] as $index => $type) {
            $invoices[] = array_merge($this->invoice($index + 1, 'RNCSMN92T22I274S'), [
                'presentation_type' => $type,
                'due_date' => ['2026-09-30', '2026-10-14', '2026-11-01', '2026-12-01'][$index],
            ]);
        }
        $response = $this->upload([['success' => true, 'page_count' => 4, 'invoices' => $invoices]])->assertOk();
        $this->assertCount(2, $this->sent);
        foreach ([[0, 2], [1, 3]] as $emailIndex => $invoiceIndexes) {
            $this->assertCount(2, $this->sent[$emailIndex]->getAttachments());
            foreach ($invoiceIndexes as $attachmentIndex => $invoiceIndex) {
                $response->assertJsonPath('results.'.$invoiceIndex.'.email_sent', true);
                $this->assertSame(base64_decode($invoices[$invoiceIndex]['pdf_base64']), $this->sent[$emailIndex]->getAttachments()[$attachmentIndex]->getBody());
            }
        }
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 30/09/2026.".self::SIGNATURE, $this->sent[0]->getTextBody());
        $this->assertSame("Gentile Cliente,\n\nin allegato il modello F24 in scadenza il 14/10/2026.\nAttendiamo la solita autorizzazione per procedere con l’addebito telematico.".self::SIGNATURE, $this->sent[1]->getTextBody());
    }

    public function test_four_same_type_contact_invoices_make_one_email_with_four_attachments(): void
    {
        $invoices = array_map(fn ($page) => array_merge($this->invoice($page, 'RNCSMN92T22I274S'), ['presentation_type' => 'telematico_entratel']), range(1, 4));
        $this->upload([['success' => true, 'page_count' => 4, 'invoices' => $invoices]])->assertOk();
        $this->assertCount(1, $this->sent);
        $this->assertCount(4, $this->sent[0]->getAttachments());
    }

    public function test_contact_and_parent_client_invoices_are_not_merged(): void
    {
        $this->upload([['success' => true, 'page_count' => 2, 'invoices' => [
            $this->invoice(1), $this->invoice(2, 'RNCSMN92T22I274S'),
        ]]])->assertOk();
        $this->assertCount(2, $this->sent);
        $this->assertCount(1, $this->sent[0]->getAttachments());
        $this->assertCount(1, $this->sent[1]->getAttachments());
    }

    public function test_one_presentation_group_failure_does_not_block_other_type_for_same_person(): void
    {
        Mail::swap(\Mockery::mock());
        Mail::shouldReceive('raw')->once()->ordered()->andThrow(new \RuntimeException('SMTP failure'));
        Mail::shouldReceive('raw')->once()->ordered()->andReturnNull();
        $this->upload([['success' => true, 'page_count' => 3, 'invoices' => [
            $this->invoice(1),
            array_merge($this->invoice(2), ['presentation_type' => 'telematico_entratel']),
            $this->invoice(3),
        ]]])->assertOk()->assertJsonPath('results.0.email_sent', false)
            ->assertJsonPath('results.0.email_error', 'SMTP failure')
            ->assertJsonPath('results.1.email_sent', true)
            ->assertJsonPath('results.2.email_sent', false)
            ->assertJsonPath('results.2.email_error', 'SMTP failure');
    }

    public function test_missing_unknown_type_and_deleted_contact_are_skipped_without_guessing_template(): void
    {
        DB::table('client_contacts')->where('id', 10)->update(['deleted_at' => '2026-10-01']);
        $missing = $this->invoice(1);
        unset($missing['presentation_type']);
        $unknown = array_merge($this->invoice(2), ['presentation_type' => 'unrecognized']);
        $this->upload([['success' => true, 'page_count' => 4, 'invoices' => [
            $missing, $unknown, $this->invoice(3, 'RNCSMN92T22I274S'), $this->invoice(4),
        ]]])->assertOk()->assertJsonPath('results.0.email_sent', false)
            ->assertJsonPath('results.1.email_sent', false)
            ->assertJsonPath('results.2.contact_found', false)
            ->assertJsonPath('results.2.email_sent', false)
            ->assertJsonPath('results.3.email_sent', true);
        $this->assertCount(1, $this->sent);
        $this->assertCount(1, $this->sent[0]->getAttachments());
    }
}
