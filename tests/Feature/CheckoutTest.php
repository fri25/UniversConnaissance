<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const FORM = ['accept_terms' => '1', 'phone_country' => 'BJ', 'phone_number' => '+229 01 97 00 00 00'];

    public function test_guest_is_redirected_to_login(): void
    {
        $book = Book::factory()->create();

        $this->get(route('checkout.show', $book))->assertRedirect('/login');
        $this->post(route('checkout.store', $book))->assertRedirect('/login');
    }

    public function test_terms_must_be_accepted(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $book))
            ->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_creates_pending_order_and_redirects_to_gateway(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 3500]);

        $response = $this->actingAs($user)->post(route('checkout.store', $book), self::FORM);

        $order = Order::sole();
        $response->assertRedirect(route('payment.fake.show', $order));
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(3500, $order->amount);
        $this->assertSame('XOF', $order->currency);
        $this->assertNotNull($order->payment_reference);
    }

    public function test_retrying_checkout_reuses_pending_order(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post(route('checkout.store', $book), self::FORM);
        $this->actingAs($user)->post(route('checkout.store', $book), self::FORM);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_already_purchased_book_cannot_be_bought_again(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)
            ->get(route('checkout.show', $order->book))
            ->assertRedirect(route('books.show', $order->book));

        $this->actingAs($order->user)
            ->post(route('checkout.store', $order->book), self::FORM)
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_inactive_book_cannot_be_purchased(): void
    {
        $book = Book::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $book), self::FORM)
            ->assertForbidden();
    }

    public function test_simulated_payment_marks_order_paid_and_queues_delivery_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create();

        $this->actingAs($order->user)
            ->post(route('payment.fake.complete', $order), ['outcome' => 'success'])
            ->assertRedirect(route('checkout.return', $order));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->download);
        Mail::assertQueued(EbookDelivered::class, fn ($mail) => $mail->hasTo($order->user->email));

        $this->actingAs($order->user)->get(route('checkout.return', $order))->assertSee('Paiement confirmé');
        $this->actingAs($order->user)->get('/dashboard')->assertSee($order->book->title);
    }

    public function test_users_cannot_see_or_pay_other_users_orders(): void
    {
        $order = Order::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('checkout.return', $order))->assertForbidden();
        $this->actingAs($intruder)->post(route('payment.fake.complete', $order), ['outcome' => 'success'])->assertForbidden();
        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_phone_is_required_and_saved_on_profile(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('checkout.store', $book), ['accept_terms' => '1'])
            ->assertSessionHasErrors(['phone_country', 'phone_number']);

        $this->actingAs($user)->post(route('checkout.store', $book), self::FORM);
        $this->assertSame('+229 01 97 00 00 00', $user->fresh()->phone);
        $this->assertSame('BJ', $user->fresh()->phone_country);
    }

    private function useChariow(): void
    {
        config([
            'payment.default' => 'chariow',
            'payment.gateways.chariow.api_key' => 'sk_live_test',
            'payment.gateways.chariow.pulse_secret' => 'whsec_test',
        ]);
    }

    public function test_chariow_checkout_redirects_to_hosted_payment_page(): void
    {
        $this->useChariow();
        Http::fake([
            'api.chariow.com/v1/checkout' => Http::response(['data' => [
                'step' => 'payment',
                'purchase' => ['id' => 'sal_abc123', 'status' => 'awaiting_payment'],
                'payment' => ['checkout_url' => 'https://pay.chariow.test/checkout?token=abc', 'transaction_id' => 'txn_1'],
            ]]),
        ]);

        $user = User::factory()->create(['name' => 'Aïcha Diallo', 'email' => 'aicha@example.com']);
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre']);

        $this->actingAs($user)
            ->post(route('checkout.store', $book), self::FORM)
            ->assertRedirect('https://pay.chariow.test/checkout?token=abc');

        $order = Order::sole();
        $this->assertSame('sal_abc123', $order->payment_reference);
        $this->assertSame('chariow', $order->gateway);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.chariow.com/v1/checkout'
            && $request->hasHeader('Authorization', 'Bearer sk_live_test')
            && $request['product_id'] === 'prd_livre'
            && $request['email'] === 'aicha@example.com'
            && $request['first_name'] === 'Aïcha'
            && $request['last_name'] === 'Diallo'
            && $request['phone'] === ['number' => '0197000000', 'country_code' => 'BJ']
            && $request['custom_metadata'] === ['order_reference' => $order->reference]
            && $request['redirect_url'] === route('checkout.return', $order));
    }

    public function test_book_without_chariow_product_cannot_be_paid(): void
    {
        $this->useChariow();
        Http::fake();
        $book = Book::factory()->create(['chariow_product_id' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('checkout.show', $book))->assertSee('pas encore disponible');
        $this->actingAs($user)
            ->from(route('checkout.show', $book))
            ->post(route('checkout.store', $book), self::FORM)
            ->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_chariow_error_shows_friendly_message(): void
    {
        $this->useChariow();
        Http::fake(['*' => Http::response(['message' => 'Validation failed', 'errors' => ['email' => ['invalid']]], 422)]);
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre']);

        $this->actingAs(User::factory()->create())
            ->from(route('checkout.show', $book))
            ->post(route('checkout.store', $book), self::FORM)
            ->assertRedirect(route('checkout.show', $book))
            ->assertSessionHas('error');
    }

    public function test_return_page_verifies_sale_status_with_chariow(): void
    {
        Mail::fake();
        $this->useChariow();
        Http::fake(['api.chariow.com/v1/sales/sal_555' => Http::response(['data' => ['id' => 'sal_555', 'status' => 'completed']])]);
        $order = Order::factory()->create(['gateway' => 'chariow', 'payment_reference' => 'sal_555']);

        $this->actingAs($order->user)->get(route('checkout.return', $order))->assertSee('Paiement confirmé');

        $this->assertTrue($order->fresh()->isPaid());
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_return_page_keeps_waiting_while_sale_is_pending(): void
    {
        $this->useChariow();
        Http::fake(['api.chariow.com/v1/sales/*' => Http::response(['data' => ['status' => 'awaiting_payment']])]);
        $order = Order::factory()->create(['gateway' => 'chariow', 'payment_reference' => 'sal_556']);

        $this->actingAs($order->user)->get(route('checkout.return', $order))->assertSee('en cours de confirmation');
        $this->assertTrue($order->fresh()->isPending());
    }
}
