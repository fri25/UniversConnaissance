<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_buyer_cannot_review(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $book), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_pending_order_does_not_allow_review(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->user)
            ->post(route('reviews.store', $order->book), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_buyer_can_review_once(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)
            ->post(route('reviews.store', $order->book), ['rating' => 4, 'comment' => 'Très bon livre'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', ['user_id' => $order->user_id, 'book_id' => $order->book_id, 'rating' => 4]);
        $this->get(route('books.show', $order->book))->assertSee('Très bon livre');

        $this->actingAs($order->user)
            ->post(route('reviews.store', $order->book), ['rating' => 1])
            ->assertForbidden();
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_is_validated(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($order->user)
            ->post(route('reviews.store', $order->book), ['rating' => 9])
            ->assertSessionHasErrors('rating');
    }

    public function test_author_can_update_but_others_cannot(): void
    {
        $review = Review::factory()->create(['rating' => 2]);

        $this->actingAs($review->user)->put(route('reviews.update', $review), ['rating' => 5])->assertRedirect();
        $this->assertSame(5, $review->fresh()->rating);

        $this->actingAs(User::factory()->create())->put(route('reviews.update', $review), ['rating' => 1])->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('reviews.destroy', $review))->assertForbidden();
    }

    public function test_hidden_reviews_are_not_displayed_nor_counted(): void
    {
        $book = Book::factory()->create();
        Review::factory()->for($book)->create(['comment' => 'Avis publié', 'rating' => 5]);
        Review::factory()->for($book)->create(['comment' => 'Avis masqué', 'rating' => 1, 'is_approved' => false]);

        $this->get(route('books.show', $book))
            ->assertSee('Avis publié')
            ->assertDontSee('Avis masqué')
            ->assertSee('1 avis vérifié');
    }
}
