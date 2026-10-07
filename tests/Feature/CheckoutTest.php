<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Achat sans connexion : « Acheter » envoie directement sur la page de paiement.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function useChariow(): void
    {
        config(['payment.default' => 'chariow', 'payment.gateways.chariow.pulse_secret' => 'whsec_test']);
    }

    public function test_guest_is_sent_straight_to_chariow_payment_page(): void
    {
        $this->useChariow();
        $book = Book::factory()->create([
            'chariow_product_id' => 'prd_livre',
            'chariow_product_url' => 'https://boutique.mychariow.com/p/livre',
        ]);

        $this->get(route('books.buy', $book))->assertRedirect('https://boutique.mychariow.com/p/livre');
        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_buy_buttons_need_no_login(): void
    {
        $this->useChariow();
        $book = Book::factory()->create([
            'title' => 'Livre en vente',
            'chariow_product_id' => 'prd_livre',
            'chariow_product_url' => 'https://boutique.mychariow.com/p/livre',
        ]);

        $this->get(route('books.show', $book))
            ->assertSee(route('books.buy', $book))
            ->assertSee('Acheter maintenant');
        $this->get('/')->assertSee(route('books.buy', $book));
    }

    public function test_book_not_linked_to_chariow_is_not_purchasable(): void
    {
        $this->useChariow();
        $book = Book::factory()->create(['chariow_product_id' => 'prd_livre', 'chariow_product_url' => null]);

        $this->get(route('books.show', $book))->assertSee('Bientôt disponible')->assertDontSee('Acheter maintenant');
        $this->get(route('books.buy', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('error');
    }

    public function test_inactive_book_cannot_be_bought(): void
    {
        $book = Book::factory()->inactive()->create();

        $this->get(route('books.buy', $book))->assertNotFound();
    }

    public function test_owner_is_sent_to_library_instead_of_paying_twice(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)
            ->get(route('books.buy', $order->book))
            ->assertRedirect(route('dashboard'));
    }

    public function test_simulated_payment_without_account_creates_customer_and_delivers(): void
    {
        Mail::fake();
        $book = Book::factory()->create(['price' => 3500]);

        $this->get(route('books.buy', $book))->assertRedirect(route('payment.fake.show', $book));
        $this->get(route('payment.fake.show', $book))->assertOk()->assertSee('Payer');

        $response = $this->post(route('payment.fake.complete', $book), [
            'name' => 'Awa Diallo', 'email' => 'Awa@Example.com', 'phone' => '+229 01 97 00 00 00', 'outcome' => 'success',
        ]);

        $order = Order::sole();
        $response->assertRedirect(route('checkout.thanks', ['sale' => $order->payment_reference]));
        $this->assertTrue($order->isPaid());
        $this->assertSame(3500, $order->amount);

        $user = $order->user;
        $this->assertSame('awa@example.com', $user->email);
        $this->assertSame('Awa Diallo', $user->name);
        $this->assertTrue($user->is_guest);
        $this->assertGuest();

        Mail::assertQueued(EbookDelivered::class, fn ($mail) => $mail->hasTo('awa@example.com'));

        $this->get(route('checkout.thanks', ['sale' => $order->payment_reference]))
            ->assertSee('Paiement confirmé')
            ->assertSee('Télécharger (PDF)')
            ->assertSee('aw•');
    }

    public function test_purchase_with_existing_email_is_attached_to_that_account(): void
    {
        Mail::fake();
        $client = User::factory()->create(['email' => 'client@example.com']);
        $book = Book::factory()->create();

        $this->post(route('payment.fake.complete', $book), ['name' => 'X', 'email' => 'client@example.com', 'outcome' => 'success']);

        $this->assertTrue($client->hasPurchased($book));
        $this->assertFalse($client->fresh()->is_guest);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_failed_simulated_payment_records_nothing(): void
    {
        $book = Book::factory()->create();

        $this->from(route('payment.fake.show', $book))
            ->post(route('payment.fake.complete', $book), ['name' => 'X', 'email' => 'x@example.com', 'outcome' => 'failure'])
            ->assertRedirect(route('payment.fake.show', $book))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_simulation_is_unavailable_with_real_gateway(): void
    {
        $this->useChariow();
        $book = Book::factory()->create();

        $this->get(route('payment.fake.show', $book))->assertNotFound();
        $this->post(route('payment.fake.complete', $book), ['name' => 'X', 'email' => 'x@example.com', 'outcome' => 'success'])->assertNotFound();
    }

    public function test_thanks_page_without_known_sale_invites_to_check_email(): void
    {
        $this->get(route('checkout.thanks'))->assertOk()->assertSee('lien de téléchargement');
        $this->get(route('checkout.thanks', ['sale' => 'sal_inconnu']))->assertOk()->assertDontSee('Paiement confirmé');
    }

    public function test_thanks_page_does_not_expose_old_sales(): void
    {
        $order = Order::factory()->paid()->create(['paid_at' => now()->subDay()]);

        $this->get(route('checkout.thanks', ['sale' => $order->payment_reference]))->assertDontSee('Télécharger (PDF)');
    }

    public function test_guest_sets_password_and_then_sees_library(): void
    {
        Mail::fake();
        $book = Book::factory()->create(['title' => 'Mon premier achat']);
        $this->post(route('payment.fake.complete', $book), ['name' => 'Awa', 'email' => 'awa@example.com', 'outcome' => 'success']);
        $user = User::where('email', 'awa@example.com')->firstOrFail();

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'awa@example.com', 'password' => 'nouveau-mdp-123', 'password_confirmation' => 'nouveau-mdp-123',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->is_guest);
        $this->post('/login', ['email' => 'awa@example.com', 'password' => 'nouveau-mdp-123']);
        $this->get('/dashboard')->assertSee('Mon premier achat');
    }
}
