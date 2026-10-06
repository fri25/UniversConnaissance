<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_phone_is_stored_and_validated(): void
    {
        $this->post('/register', [
            'name' => 'Awa', 'email' => 'awa@example.com', 'phone' => '+229 01 97 00 00 00',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'awa@example.com', 'phone' => '+229 01 97 00 00 00', 'is_admin' => false]);

        auth()->logout();
        $this->post('/register', [
            'name' => 'Bad', 'email' => 'bad@example.com', 'phone' => 'pas un numéro',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');
    }

    public function test_registration_returns_to_intended_checkout(): void
    {
        $book = Book::factory()->create();
        $this->get(route('checkout.show', $book))->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Client', 'email' => 'client@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('checkout.show', $book));
    }
}
