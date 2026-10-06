<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\DownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EbookDelivered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre e-book « '.$this->order->book->title.' » est prêt',
        );
    }

    public function content(): Content
    {
        $downloads = app(DownloadService::class);
        $download = $downloads->ensureFresh($this->order->download()->firstOrFail());

        $links = collect($this->order->book->availableFormats())
            ->mapWithKeys(fn (string $format) => [strtoupper($format) => $downloads->signedUrl($download, $format)]);

        return new Content(
            markdown: 'mail.ebook-delivered',
            with: [
                'order' => $this->order,
                'book' => $this->order->book,
                'download' => $download,
                'links' => $links,
            ],
        );
    }
}
