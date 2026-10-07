<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MetaPixel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Transmet un achat confirmé à Meta (API Conversions), en file d'attente pour
 * ne jamais retarder ni faire échouer le traitement du paiement.
 */
class SendMetaPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function handle(MetaPixel $meta): void
    {
        $meta->sendPurchase($this->order);
    }
}
