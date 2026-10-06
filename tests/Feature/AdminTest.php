<?php

namespace Tests\Feature;

use App\Mail\EbookDelivered;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    public function test_admin_area_is_restricted(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/books')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Chiffre d');
    }

    public function test_is_admin_cannot_be_mass_assigned_at_registration(): void
    {
        $this->post('/register', [
            'name' => 'Pirate', 'email' => 'pirate@test.com', 'is_admin' => 1,
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->assertFalse(User::where('email', 'pirate@test.com')->first()->is_admin);
    }

    public function test_admin_creates_book_with_files_in_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $author = Author::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.books.store'), [
            'title' => 'Nouveau livre',
            'language' => 'fr',
            'format' => 'pdf',
            'price' => 3000,
            'old_price' => 4000,
            'is_active' => '1',
            'authors' => [$author->id],
            'categories' => [$category->id],
            'cover' => UploadedFile::fake()->image('cover.jpg', 400, 600),
            'file' => UploadedFile::fake()->create('livre.pdf', 120, 'application/pdf'),
            'sample' => UploadedFile::fake()->create('extrait.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('admin.books.index'));

        $book = Book::where('slug', 'nouveau-livre')->firstOrFail();
        $this->assertSame(4000, $book->old_price);
        Storage::disk('local')->assertExists($book->file_path);
        Storage::disk('local')->assertExists($book->sample_path);
        Storage::disk('public')->assertExists($book->cover);
        Storage::disk('public')->assertMissing($book->file_path);
        $this->assertTrue($book->authors->contains($author));
    }

    public function test_book_validation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.books.store'), ['title' => '', 'price' => -1, 'old_price' => 0, 'format' => 'docx'])
            ->assertSessionHasErrors(['title', 'price', 'format', 'authors', 'categories', 'file']);
    }

    public function test_sold_book_is_deactivated_instead_of_deleted(): void
    {
        $order = Order::factory()->paid()->create();

        $this->actingAs($this->admin)->delete(route('admin.books.destroy', $order->book));

        $this->assertDatabaseHas('books', ['id' => $order->book_id, 'is_active' => false]);
    }

    public function test_admin_can_manage_authors_and_categories(): void
    {
        $this->actingAs($this->admin)->post(route('admin.authors.store'), ['name' => 'Mariama Bâ'])->assertRedirect();
        $this->assertDatabaseHas('authors', ['slug' => 'mariama-ba']);

        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'Poésie'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'poesie']);
    }

    public function test_orders_can_be_filtered_and_delivery_email_resent(): void
    {
        Mail::fake();
        $paid = Order::factory()->paid()->create();
        $pending = Order::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'paid']))
            ->assertSee($paid->reference)
            ->assertDontSee($pending->reference);

        $this->actingAs($this->admin)->post(route('admin.orders.resend', $paid))->assertSessionHas('status');
        Mail::assertQueued(EbookDelivered::class, 1);

        $this->actingAs($this->admin)->post(route('admin.orders.resend', $pending))->assertSessionHas('error');
        Mail::assertQueued(EbookDelivered::class, 1);
    }

    public function test_admin_moderates_reviews(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->admin)->patch(route('admin.reviews.toggle', $review));
        $this->assertFalse($review->fresh()->is_approved);

        $this->actingAs($this->admin)->delete(route('admin.reviews.destroy', $review));
        $this->assertModelMissing($review);
    }

    public function test_csv_import_creates_inactive_books(): void
    {
        $csv = "title;authors;categories;price;old_price;format\nLivre importé;Jean Dupont|Awa Diallo;Business;3500;5000;pdf\n;;;;;\nSans prix;X;Y;;;pdf\n";

        $this->actingAs($this->admin)->post(route('admin.books.import.store'), [
            'csv' => UploadedFile::fake()->createWithContent('livres.csv', $csv),
        ])->assertRedirect()->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1);

        $book = Book::where('title', 'Livre importé')->firstOrFail();
        $this->assertFalse($book->is_active);
        $this->assertCount(2, $book->authors);
        $this->assertSame(5000, $book->old_price);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-admin', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_admin);
    }
}
