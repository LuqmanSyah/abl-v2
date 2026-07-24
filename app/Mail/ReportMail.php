<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $rows,
        public array $filters,
        public Collection $periods,
        public string $dateLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan SDM Periodik — ' . $this->dateLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report',
            with: [
                'dateLabel' => $this->dateLabel,
                'employeeCount' => $this->rows->count(),
            ],
        );
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('reports.hr-pdf', [
            'filters' => $this->filters,
            'rows' => $this->rows,
            'periods' => $this->periods,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'laporan-sdm-' . $this->dateLabel . '.pdf'),
        ];
    }
}
