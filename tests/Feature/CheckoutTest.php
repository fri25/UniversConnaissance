<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Achat sans connexion : formulaire (nom, email, téléphone) → paiement créé par
 * l'API du prestataire → page de paiement → retour signé.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const FORM = [
        'name' => 'Aïcha Diallo',
        'email' => 'Aicha@Example.com',
        'phone_country' => 'BJ',
        'phone_number' => '+229 01 97 00 00 00',
        'accept_terms' => '1',
    ];

    private function useChariow(): void
    {
        config([
            'payment.default' => 'chariow',
            'payment.gateways.chariow.api_key' => 'sk_live_test',
            'payment.gateways.chariow.pulse_secret' => 'whsec_test',
        ]);
    }

    private function fakeChariowCheckout(string $saleId = 'sal_abc123'): void
    {
        Http::fake([
            'api.chariow.com/v1/checkout' => Http::response(['data' => [
                'step' => 'payment',
                'purchase' => ['id' => $saleId, 'status' => 'awaiting_payment'],
                'payment' => ['checkout_url' => 'https://pay.chariow.test/checkout?token=abc', 'transaction_id' => 'txn_1'],
            ]]),
        ]);
    }

    public function test_buy_button_opens_form_without_login(): void
    {
        $book = Book::factory()->create(['title' => 'Livre en vente']);

        $this->get(route('books.show', $book))->assertSee(route('checkout.show', $book))->assertSee('Acheter maintenant');
        $this->get(route('checkout.show', $book))
            ->assertOk()
            ->assertSee('Finaliser votre achat')
            ->assertSee('name="email"', false)
            ->assertSee('name="phone_number"', false);
        $this->assertGuest();
    }

    public function test_guest_checkout_creates_customer_order_and_redirects_to_chariow(): void
    {
        $this->useChariow();
        $this->fakeChariowCheckout();
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre', 'price' => 3500]);

        $this->post(route('checkout.store', $book), self::FORM)
            ->assertRedirect('https://pay.chariow.test/checkout?token=abc');

        $this->assertGuest();
        $order = Order::sole();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('sal_abc123', $order->payment_reference);
        $this->assertSame('chariow', $order->gateway);
        $this->assertSame(3500, $order->amount);

        $customer = $order->user;
        $this->assertSame('aicha@example.com', $customer->email);
        $this->assertSame('Aïcha Diallo', $customer->name);
        $this->assertTrue($customer->is_guest);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.chariow.com/v1/checkout'
            && $request->hasHeader('Authorization', 'Bearer sk_live_test')
            && $request['product_id'] === 'prd_livre'
            && $request['email'] === 'aicha@example.com'
            && $request['first_name'] === 'Aïcha'
            && $request['last_name'] === 'Diallo'
            && $request['phone'] === ['number' => '0197000000', 'country_code' => 'BJ']
            && $request['payment_currency'] === 'XOF'
            && $request['custom_metadata'] === ['order_reference' => $order->reference]
            && str_contains($request['redirect_url'], '/merci/'.$order->reference)
            && str_contains($request['redirect_url'], 'signature='));
    }

    public function test_form_is_validated(): void
    {
        $book = Book::factory()->create();

        $this->post(route('checkout.store', $book), ['email' => 'pas-un-email'])
            ->assertSessionHasErrors(['name', 'email', 'phone_country', 'phone_number', 'accept_terms']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_retrying_reuses_the_pending_order(): void
    {
        $book = Book::factory()->create();

        $this->post(route('checkout.store', $book), self::FORM);
        $this->post(route('checkout.store', $book), self::FORM);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_account_details_are_not_overwritten(): void
    {
        $client = User::factory()->create(['email' => 'aicha@example.com', 'name' => 'Vrai nom', 'phone' => '0102030405', 'phone_country' => 'CI']);
        $book = Book::factory()->create();

        $this->post(route('checkout.store', $book), self::FORM);

        $client->refresh();
        $this->assertSame('Vrai nom', $client->name);
        $this->assertSame('0102030405', $client->phone);
        $this->assertSame($client->id, Order::sole()->user_id);
    }

    public function test_email_that_already_owns_the_book_is_told_so(): void
    {
        $order = Order::factory()->paid()->create();
        $order->user->update(['email' => 'aicha@example.com']);

        $this->from(route('checkout.show', $order->book))
            ->post(route('checkout.store', $order->book), self::FORM)
            ->assertRedirect(route('checkout.show', $order->book))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_logged_in_user_buys_with_own_account(): void
    {
        $user = User::factory()->create(['email' => 'moi@example.com']);
        $book = Book::factory()->create();

        $this->actingAs($user)->get(route('checkout.show', $book))->assertSee('moi@example.com')->assertDontSee('name="email"', false);
        $this->actingAs($user)->post(route('checkout.store', $book), ['email' => 'autre@example.com'] + self::FORM);

        $this->assertSame($user->id, Order::sole()->user_id);
    }

    public function test_owner_is_sent_to_library(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)->get(route('checkout.show', $order->book))->assertRedirect(route('dashboard'));
    }

    public function test_book_not_linked_to_chariow_is_not_purchasable(): void
    {
        $this->useChariow();
        Http::fake();
        $book = Book::factory()->create(['chariow_product_id' => null]);

        $this->get(route('books.show', $book))->assertSee('Bientôt disponible')->assertDontSee('Acheter maintenant');
        $this->get(route('checkout.show', $book))->assertRedirect(route('books.show', $book));
        Http::assertNothingSent();
    }

    public function test_inactive_book_cannot_be_bought(): void
    {
        $book = Book::factory()->inactive()->create();

        $this->get(route('checkout.show', $book))->assertNotFound();
        $this->post(route('checkout.store', $book), self::FORM)->assertNotFound();
    }

    public function test_chariow_error_shows_friendly_message(): void
    {
        $this->useChariow();
        Http::fake(['*' => Http::response(['message' => 'Validation failed'], 422)]);
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre']);

        $this->from(route('checkout.show', $book))
            ->post(route('checkout.store', $book), self::FORM)
            ->assertRedirect(route('checkout.show', $book))
            ->assertSessionHas('error');
    }

    public function test_admin_sees_exact_payment_error_but_customers_do_not(): void
    {
        $this->useChariow();
        config(['payment.gateways.chariow.api_key' => null]);
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre']);

        $this->post(route('checkout.store', $book), self::FORM)
            ->assertSessionHas('error', fn ($m) => ! str_contains($m, 'CHARIOW_API_KEY'));

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->post(route('checkout.store', $book), self::FORM)
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'CHARIOW_API_KEY non configurée'));
    }

    public function test_return_page_verifies_sale_with_chariow_and_offers_download(): void
    {
        Mail::fake();
        $this->useChariow();
        Http::fake(['api.chariow.com/v1/sales/sal_555' => Http::response(['data' => ['id' => 'sal_555', 'status' => 'completed']])]);
        $order = Order::factory()->create(['gateway' => 'chariow', 'payment_reference' => 'sal_555']);

        $this->get($order->returnUrl())
            ->assertOk()
            ->assertSee('Paiement confirmé')
            ->assertSee('Télécharger (PDF)');

        $this->assertTrue($order->fresh()->isPaid());
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_return_page_waits_while_sale_is_pending(): void
    {
        $this->useChariow();
        Http::fake(['api.chariow.com/v1/sales/*' => Http::response(['data' => ['status' => 'awaiting_payment']])]);
        $order = Order::factory()->create(['gateway' => 'chariow', 'payment_reference' => 'sal_556']);

        $this->get($order->returnUrl())->assertSee('en cours de confirmation')->assertDontSee('Télécharger (PDF)');
    }

    public function test_return_page_requires_valid_signature(): void
    {
        $order = Order::factory()->paid()->create();

        $this->get(route('checkout.return', $order))->assertForbidden();
    }

    public function test_full_simulated_purchase_without_account(): void
    {
        Mail::fake();
        $book = Book::factory()->create(['price' => 2500]);

        $redirect = $this->post(route('checkout.store', $book), self::FORM)->headers->get('Location');
        $order = Order::sole();
        $this->assertStringContainsString('/paiement/simulation/'.$order->reference, $redirect);
        $this->assertStringContainsString('signature=', $redirect);

        // Sans signature : refusé (on ne peut pas « payer » la commande de quelqu'un d'autre).
        $this->get(route('payment.fake.show', $order))->assertForbidden();

        $this->get($redirect)->assertOk()->assertSee('Confirmer le paiement');

        $complete = URL::temporarySignedRoute('payment.fake.complete', now()->addHour(), ['order' => $order->reference]);
        $back = $this->post($complete, ['outcome' => 'success'])->headers->get('Location');

        $this->assertTrue($order->fresh()->isPaid());
        Mail::assertQueued(EbookDelivered::class, fn ($mail) => $mail->hasTo('aicha@example.com'));
        $this->get($back)->assertSee('Paiement confirmé')->assertSee('Télécharger (PDF)');
        $this->assertGuest();
    }

    public function test_simulated_failure_marks_order_failed(): void
    {
        $book = Book::factory()->create();
        $this->post(route('checkout.store', $book), self::FORM);
        $order = Order::sole();

        $complete = URL::temporarySignedRoute('payment.fake.complete', now()->addHour(), ['order' => $order->reference]);
        $back = $this->post($complete, ['outcome' => 'failure'])->headers->get('Location');

        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);
        $this->get($back)->assertSee('pas abouti')->assertSee('Réessayer');
    }

    public function test_simulation_is_unavailable_with_real_gateway(): void
    {
        $order = Order::factory()->create(['gateway' => 'chariow']);
        $url = URL::temporarySignedRoute('payment.fake.show', now()->addHour(), ['order' => $order->reference]);

        $this->get($url)->assertNotFound();
    }

    public function test_guest_sets_password_and_then_sees_library(): void
    {
        Mail::fake();
        $book = Book::factory()->create(['title' => 'Mon premier achat']);
        $this->post(route('checkout.store', $book), self::FORM);
        $order = Order::sole();
        $this->post(URL::temporarySignedRoute('payment.fake.complete', now()->addHour(), ['order' => $order->reference]), ['outcome' => 'success']);

        $user = $order->user;
        $this->post('/reset-password', [
            'token' => Password::broker()->createToken($user), 'email' => $user->email,
            'password' => 'nouveau-mdp-123', 'password_confirmation' => 'nouveau-mdp-123',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->is_guest);
        $this->post('/login', ['email' => $user->email, 'password' => 'nouveau-mdp-123']);
        $this->get('/dashboard')->assertSee('Mon premier achat');
    }
}
