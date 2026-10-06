<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_active_books_only(): void
    {
        Book::factory()->create(['title' => 'Livre visible']);
        Book::factory()->inactive()->create(['title' => 'Livre caché']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Explorez l', false)
            ->assertSee('Livre visible')
            ->assertDontSee('Livre caché');
    }

    public function test_catalog_filters_by_category_format_and_promo(): void
    {
        $sciences = Category::factory()->create(['slug' => 'sciences']);
        $inCategory = Book::factory()->onPromo()->create(['title' => 'Astronomie pratique']);
        $inCategory->categories()->sync([$sciences->id]);
        Book::factory()->create(['title' => 'Roman sans promo', 'format' => 'epub']);

        $this->get('/livres?category=sciences')->assertSee('Astronomie pratique')->assertDontSee('Roman sans promo');
        $this->get('/livres?promo=1')->assertSee('Astronomie pratique')->assertDontSee('Roman sans promo');
        $this->get('/livres?format=epub')->assertSee('Roman sans promo')->assertDontSee('Astronomie pratique');
        $this->get('/categorie/sciences')->assertOk()->assertSee('Astronomie pratique');
    }

    public function test_catalog_search_matches_author_name(): void
    {
        $book = Book::factory()->create(['title' => 'Un titre neutre']);
        $book->authors->first()->update(['name' => 'Victor Hugo']);

        $this->get('/livres?q=hugo')->assertSee('Un titre neutre');
        $this->get('/livres?q=inexistant')->assertSee('Aucun e-book');
    }

    public function test_invalid_filters_are_ignored(): void
    {
        $this->get('/livres?sort=hack&format=docx&min_price=abc')->assertOk();
    }

    public function test_product_page_shows_details_and_structured_data(): void
    {
        $book = Book::factory()->onPromo(1500, 3000)->create(['title' => 'Fiche complète']);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Fiche complète')
            ->assertSee('−50 %', false)
            ->assertSee('"@type":"Book"', false)
            ->assertSee('https://schema.org/EBook', false)
            ->assertSee('Acheter maintenant');
    }

    public function test_inactive_product_returns_404(): void
    {
        $book = Book::factory()->inactive()->create();

        $this->get(route('books.show', $book))->assertNotFound();
    }

    public function test_owner_sees_download_instead_of_buy(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)
            ->get(route('books.show', $order->book))
            ->assertSee('Télécharger (PDF)')
            ->assertDontSee('Acheter maintenant');
    }

    public function test_static_pages_are_available(): void
    {
        foreach (['/aide', '/mentions-legales', '/cgv', '/confidentialite'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Mes achats');
    }
}
