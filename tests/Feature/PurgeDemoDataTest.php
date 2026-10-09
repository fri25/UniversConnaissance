<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeDemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DemoCatalogSeeder::class);
    }

    private function realAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'patron@example.com']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_preview_deletes_nothing(): void
    {
        $this->realAdmin();

        $this->artisan('demo:purge')->expectsOutputToContain('Aperçu uniquement')->assertSuccessful();

        $this->assertSame(12, Book::count());
        $this->assertDatabaseHas('users', ['email' => 'admin@universconnaissance.test']);
    }

    public function test_refuses_when_demo_admin_is_the_only_admin(): void
    {
        $this->artisan('demo:purge --force')->expectsOutputToContain('uc:make-admin')->assertFailed();

        $this->assertSame(12, Book::count());
    }

    public function test_force_removes_demo_data_and_keeps_real_content(): void
    {
        $admin = $this->realAdmin();
        $category = Category::where('slug', 'business')->first();
        $realBook = Book::factory()->create(['title' => 'Mon vrai livre', 'cover' => 'covers/vrai.png']);
        $realBook->categories()->sync([$category->id]);
        Storage::disk('public')->put('covers/vrai.png', 'img');
        $realSale = Order::factory()->paid()->for($realBook)->create(['gateway' => 'chariow', 'payment_reference' => 'sal_reel']);
        $fakeTest = Order::factory()->paid()->for($realBook)->create(['gateway' => 'fake']);
        $guestTester = $fakeTest->user;
        $guestTester->forceFill(['is_guest' => true])->save();
        $demoCover = Book::where('slug', 'madame-bovary')->value('cover');

        $this->artisan('demo:purge --force')->assertSuccessful();

        $this->assertSame(['Mon vrai livre'], Book::pluck('title')->all());
        $this->assertDatabaseMissing('users', ['email' => 'admin@universconnaissance.test']);
        $this->assertDatabaseMissing('users', ['email' => 'client@universconnaissance.test']);
        $this->assertSame(0, User::where('email', 'like', '%@exemple.test')->count());
        $this->assertModelExists($admin);
        $this->assertModelExists($realSale);
        $this->assertModelMissing($fakeTest);
        $this->assertModelMissing($guestTester);
        $this->assertSame(0, Review::count());
        $this->assertSame(5, Category::whereIn('slug', array_keys(DemoCatalogSeeder::CATEGORIES))->count(), 'Catégories conservées par défaut');
        Storage::disk('public')->assertMissing($demoCover);
        Storage::disk('public')->assertExists('covers/vrai.png');
        $this->assertSame(0, Order::where('gateway', 'fake')->count());
    }

    public function test_option_removes_empty_demo_categories(): void
    {
        $this->realAdmin();

        $this->artisan('demo:purge --force --with-categories')->assertSuccessful();

        $this->assertSame(0, Category::count());
    }

    public function test_refuses_if_a_demo_book_has_a_real_sale(): void
    {
        $this->realAdmin();
        Order::factory()->paid()->for(Book::where('slug', 'madame-bovary')->first())->create(['gateway' => 'chariow', 'payment_reference' => 'sal_x']);

        $this->artisan('demo:purge --force')->assertFailed();

        $this->assertSame(12, Book::count());
    }

    private const ASK = 'Mot de passe (8 caractères minimum, rien ne s\'affiche)';

    private const CONFIRM = 'Confirmez le mot de passe';

    public function test_make_admin_creates_account_that_can_log_in(): void
    {
        $this->artisan('uc:make-admin Nouveau@Example.com --name=Patron')
            ->expectsQuestion(self::ASK, 'motdepasse-solide')
            ->expectsQuestion(self::CONFIRM, 'motdepasse-solide')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'nouveau@example.com')->firstOrFail()->is_admin);

        $this->post('/login', ['email' => 'nouveau@example.com', 'password' => 'motdepasse-solide'])->assertRedirect();
        $this->assertAuthenticated();
        $this->get('/admin')->assertOk();
    }

    public function test_make_admin_resets_password_of_existing_account(): void
    {
        // Ex. : compte créé automatiquement lors d'un achat test, mot de passe inconnu.
        $user = User::factory()->create(['email' => 'moi@example.com']);
        $user->forceFill(['is_guest' => true])->save();

        $this->artisan('uc:make-admin moi@example.com')
            ->expectsOutputToContain('existe déjà')
            ->expectsQuestion(self::ASK, 'nouveau-mdp-123')
            ->expectsQuestion(self::CONFIRM, 'nouveau-mdp-123')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertFalse($user->is_guest);

        $this->post('/login', ['email' => 'moi@example.com', 'password' => 'nouveau-mdp-123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_make_admin_can_keep_existing_password(): void
    {
        $user = User::factory()->create(['email' => 'moi@example.com']);
        $hash = $user->password;

        $this->artisan('uc:make-admin moi@example.com --keep-password')->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_make_admin_rejects_mismatched_confirmation(): void
    {
        $this->artisan('uc:make-admin x@example.com')
            ->expectsQuestion(self::ASK, 'motdepasse-solide')
            ->expectsQuestion(self::CONFIRM, 'autre-chose-123')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
