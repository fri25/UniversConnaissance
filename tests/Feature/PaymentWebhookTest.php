<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
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

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.default' => 'chariow',
            'payment.gateways.chariow.pulse_secret' => self::SECRET,
        ]);
        Mail::fake();

        $this->book = Book::factory()->create([
            'price' => 2500,
            'chariow_product_id' => 'prd_livre',
        ]);
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

    /**
     * Charge utile au format des Pulses Chariow.
     */
    private function sale(string $id = 'sal_1001', string $event = 'successful.sale', string $product = 'prd_livre', string $email = 'awa@example.com', int $amount = 2500): array
    {
        return [
            'event' => $event,
            'sale' => [
                'id' => $id,
                'status' => $event === 'successful.sale' ? 'completed' : 'failed',
                'amount' => ['value' => $amount, 'formatted' => $amount.' F CFA', 'currency' => 'XOF'],
                'custom_metadata' => null,
            ],
            'product' => ['id' => $product, 'name' => 'Livre'],
            'customer' => [
                'id' => 'cus_1', 'name' => 'Awa Diallo', 'first_name' => 'Awa', 'last_name' => 'Diallo',
                'email' => $email, 'phone' => '+22901970000', 'country' => 'BJ',
            ],
        ];
    }

    public function test_sale_from_guest_creates_account_order_and_sends_email(): void
    {
        $this->pulse($this->sale())->assertOk()->assertJson(['message' => 'Commande mise à jour.']);

        $order = Order::sole();
        $this->assertTrue($order->isPaid());
        $this->assertSame('sal_1001', $order->payment_reference);
        $this->assertSame('chariow', $order->gateway);
        $this->assertTrue($order->book->is($this->book));
        $this->assertNotNull($order->download);

        $user = $order->user;
        $this->assertSame('awa@example.com', $user->email);
        $this->assertSame('Awa Diallo', $user->name);
        $this->assertSame('BJ', $user->phone_country);
        $this->assertTrue($user->is_guest);

        Mail::assertQueued(EbookDelivered::class, fn ($mail) => $mail->hasTo('awa@example.com'));
    }

    public function test_pulse_confirms_the_pending_order_created_by_the_checkout_api(): void
    {
        $order = Order::factory()->for($this->book)->create(['gateway' => 'chariow', 'payment_reference' => 'sal_api', 'amount' => 2500]);
        $payload = $this->sale('sal_api');
        $payload['sale']['custom_metadata'] = ['order_reference' => $order->reference];

        $this->pulse($payload)->assertOk()->assertJson(['message' => 'Commande mise à jour.']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertNotNull($order->fresh()->download);
        Mail::assertQueued(EbookDelivered::class, fn ($mail) => $mail->hasTo($order->user->email));
    }

    public function test_pulse_finds_order_by_metadata_when_sale_id_was_not_saved(): void
    {
        $order = Order::factory()->for($this->book)->create(['gateway' => 'chariow', 'payment_reference' => null, 'amount' => 2500]);
        $payload = $this->sale('sal_nouveau');
        $payload['sale']['custom_metadata'] = ['order_reference' => $order->reference];

        $this->pulse($payload)->assertOk();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('sal_nouveau', $order->fresh()->payment_reference);
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_delivery_email_offers_password_creation_to_new_customers(): void
    {
        $this->pulse($this->sale())->assertOk();

        $html = (new EbookDelivered(Order::sole()))->render();

        $this->assertStringContainsString('Créer mon mot de passe', $html);
        $this->assertStringContainsString('/reset-password/', $html);
        $this->assertStringContainsString('/telecharger/', $html);
    }

    public function test_sale_is_attached_to_existing_account_with_same_email(): void
    {
        $client = User::factory()->create(['email' => 'awa@example.com']);

        $this->pulse($this->sale(email: 'AWA@example.com'))->assertOk();

        $this->assertTrue($client->hasPurchased($this->book));
        $this->assertDatabaseCount('users', 1);
        $this->assertStringNotContainsString('Créer mon mot de passe', (new EbookDelivered(Order::sole()))->render());
    }

    public function test_pulse_is_idempotent(): void
    {
        $this->pulse($this->sale())->assertOk();
        $token = Order::sole()->download->token;

        // Chariow réessaie jusqu'à 5 fois la même livraison.
        $this->pulse($this->sale())->assertOk()->assertJson(['message' => 'Déjà traité.']);
        $this->pulse($this->sale())->assertOk();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('downloads', 1);
        $this->assertSame($token, Order::sole()->download->token);
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->pulse($this->sale(), 'sha256=deadbeef')->assertStatus(400);
        $this->pulse($this->sale(), '')->assertStatus(400);
        $this->pulse($this->sale(), ChariowGateway::sign('autre corps', self::SECRET))->assertStatus(400);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingQueued();
    }

    public function test_sale_of_unknown_product_is_not_delivered(): void
    {
        $this->pulse($this->sale(product: 'prd_inconnu'))->assertOk();

        $this->assertDatabaseCount('orders', 0);
        Mail::assertNothingQueued();
    }

    public function test_sale_without_valid_email_is_not_delivered(): void
    {
        $this->pulse($this->sale(email: 'pas-un-email'))->assertOk();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_price_difference_is_logged_and_paid_amount_recorded(): void
    {
        $this->pulse($this->sale(amount: 2000))->assertOk();

        $this->assertSame(2000, Order::sole()->amount);
    }

    public function test_failed_and_abandoned_sales_record_nothing(): void
    {
        $this->pulse($this->sale('sal_2', 'failed.sale'))->assertOk();
        $this->pulse($this->sale('sal_3', 'abandoned.sale'))->assertOk();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_failed_event_cannot_downgrade_a_paid_order(): void
    {
        $this->pulse($this->sale('sal_4'))->assertOk();
        $this->pulse($this->sale('sal_4', 'failed.sale'))->assertOk();

        $this->assertTrue(Order::sole()->isPaid());
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
