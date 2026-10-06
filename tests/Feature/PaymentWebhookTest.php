<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Book;
use App\Models\Order;
use App\Payments\Gateways\ChariowGateway;
use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.default' => 'chariow',
            'payment.gateways.chariow.api_key' => 'sk_test',
            'payment.gateways.chariow.pulse_secret' => self::SECRET,
        ]);
        Mail::fake();
    }

    private function order(array $attributes = []): Order
    {
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre', 'price' => 2500]);

        return Order::factory()->for($book)->create(['gateway' => 'chariow', 'amount' => 2500] + $attributes);
    }

    private function pulse(array $payload, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload);
        $signature ??= ChariowGateway::sign($body, self::SECRET);

        return $this->call('POST', '/webhooks/payment', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CHARIOW_SIGNATURE' => $signature,
            'HTTP_X_PULSE_DELIVERY_ID' => 'dlv_1',
        ], $body);
    }

    private function sale(Order $order, string $event = 'successful.sale', array $sale = [], string $product = 'prd_livre'): array
    {
        return [
            'event' => $event,
            'sale' => $sale + [
                'id' => $order->payment_reference,
                'status' => 'completed',
                'amount' => ['value' => $order->amount, 'currency' => 'XOF'],
                'custom_metadata' => ['order_reference' => $order->reference],
            ],
            'product' => ['id' => $product],
            'customer' => ['email' => $order->user->email],
        ];
    }

    public function test_successful_sale_marks_order_paid_and_sends_email(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1001']);

        $this->pulse($this->sale($order))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->download);
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_order_is_found_by_metadata_when_sale_id_is_unknown(): void
    {
        $order = $this->order(['payment_reference' => null]);

        $this->pulse($this->sale($order, sale: ['id' => 'sal_inconnu']))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('sal_inconnu', $order->payment_reference);
    }

    public function test_pulse_is_idempotent(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1002']);

        $this->pulse($this->sale($order))->assertOk()->assertJson(['message' => 'Commande mise à jour.']);
        $token = $order->fresh()->download->token;

        // Chariow réessaie jusqu'à 5 fois la même livraison.
        $this->pulse($this->sale($order))->assertOk()->assertJson(['message' => 'Déjà traité.']);
        $this->pulse($this->sale($order))->assertOk();

        $this->assertSame($token, $order->fresh()->download->token);
        $this->assertDatabaseCount('downloads', 1);
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1003']);

        $this->pulse($this->sale($order), 'sha256=deadbeef')->assertStatus(400);
        $this->pulse($this->sale($order), '')->assertStatus(400);
        $this->pulse($this->sale($order), ChariowGateway::sign('autre corps', self::SECRET))->assertStatus(400);

        $this->assertFalse($order->fresh()->isPaid());
        Mail::assertNothingQueued();
    }

    public function test_sale_for_another_product_does_not_validate_order(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1004']);

        $this->pulse($this->sale($order, product: 'prd_moins_cher'))->assertOk();

        $this->assertFalse($order->fresh()->isPaid());
        Mail::assertNothingQueued();
    }

    public function test_price_difference_with_chariow_is_logged_but_product_match_wins(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1005']);

        $this->pulse($this->sale($order, sale: ['amount' => ['value' => 2000, 'currency' => 'XOF']]))->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_failed_and_abandoned_sales_mark_order_failed(): void
    {
        $failed = $this->order(['payment_reference' => 'sal_1006']);
        $abandoned = $this->order(['payment_reference' => 'sal_1007']);

        $this->pulse($this->sale($failed, 'failed.sale'))->assertOk();
        $this->pulse($this->sale($abandoned, 'abandoned.sale'))->assertOk();

        $this->assertSame(Order::STATUS_FAILED, $failed->fresh()->status);
        $this->assertSame(Order::STATUS_FAILED, $abandoned->fresh()->status);
    }

    public function test_failed_event_cannot_downgrade_a_paid_order(): void
    {
        $order = $this->order(['payment_reference' => 'sal_1008', 'status' => Order::STATUS_PAID, 'paid_at' => now()]);

        $this->pulse($this->sale($order, 'failed.sale'))->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_other_events_are_acknowledged_and_ignored(): void
    {
        $this->pulse(['event' => 'license.issued', 'license' => ['key' => 'X']])
            ->assertOk()
            ->assertJson(['message' => 'Événement ignoré.']);
    }

    public function test_webhook_does_not_require_csrf_token(): void
    {
        $this->app->make(Kernel::class);
        $neverVerify = Closure::bind(fn () => static::$neverVerify, null, VerifyCsrfToken::class)();

        $this->assertContains('webhooks/*', $neverVerify);
    }
}
