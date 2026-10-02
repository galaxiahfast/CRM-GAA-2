<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $pdfContents,
        public readonly string $pdfFilename,
        public readonly string $generatedDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('backup-reports.email.from_address'),
                (string) config('backup-reports.email.from_name'),
            ),
            subject: 'Reporte de respaldos - '.$this->generatedDate,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.backup-report',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfContents, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
