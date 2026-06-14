<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage; // Add this at the top of your file to ensure Storage is available

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $header;
    public $body;
    public $files;

    public function __construct($header, $body, $files = [])
    {
        $this->header = $header;
        $this->body = $body;
        $this->files = $files;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Start building the email with subject and view
        $this->subject($this->header)
                      ->view('send_invoice_email', [
                          'header' => $this->header,
                          'body' => $this->body
                      ]);

        // Attach files if provided
            // You can use `fromStorage` if you're using files from storage
             foreach ($this->files as $file){

                $this->attach($file);

            }

        return $this;
    }
}

