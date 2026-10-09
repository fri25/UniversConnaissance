<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\MetaPixel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MetaPixelTest extends TestCase
{
    use RefreshDatabase;

    private const PIXEL = '123456789012345';

    /**
     * Achat simulé complet, sans compte : formulaire puis paiement confirmé.
     */
    private function buy(Book $book, string $email = 'x@example.com', string $name = 'Client Test'): Order
    {
        $this->post(route('checkout.store', $book), [
            'name' => $name, 'email' => $email, 'phone_country' => 'BJ', 'phone_number' => '+229 01 97 00 00 00'
        ]);
        $order = Order::sole();
        $this->post(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.fake.complete', now()->addHour(), ['order' => $order->reference]), ['outcome' => 'success']);

        return $order->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_nothing_is_loaded_without_pixel(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('fbevents.js')
            ->assertDontSee('cookies de mesure publicitaire');
    }

    public function test_pixel_is_installed_with_consent_banner_and_events(): void
    {
        Setting::put('meta_pixel_id', self::PIXEL);
        $book = Book::factory()->create(['title' => 'Livre suivi', 'price' => 3500]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('connect.facebook.net/en_US/fbevents.js', false)
            ->assertSee("fbq('init', '".self::PIXEL."')", false)
            ->assertSee('cookies de mesure publicitaire')
            ->assertSee("ucTrack('ViewContent'", false)
            ->assertSee('data-pixel-checkout', false)
            ->assertDontSee('facebook.com/tr?id=', false);

        $this->get(route('books.index', ['q' => 'livre']))->assertSee("ucTrack('Search'", false);
    }

    public function test_pixel_is_not_loaded_in_admin(): void
    {
        Setting::put('meta_pixel_id', self::PIXEL);

        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertDontSee('fbevents.js');
    }

    public function test_admin_configures_pixel_and_token_is_encrypted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Pixel désactivé');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'meta_pixel_id' => self::PIXEL,
            'meta_capi_token' => 'EAABsecret',
        ])->assertSessionHas('status');

        $this->assertSame(self::PIXEL, app(MetaPixel::class)->pixelId());
        $this->assertSame('EAABsecret', app(MetaPixel::class)->capiToken());
        $this->assertNotSame('EAABsecret', DB::table('settings')->where('key', 'meta_capi_token')->value('value'));

        // Champ jeton laissé vide : le jeton est conservé.
        $this->actingAs($admin)->put(route('admin.settings.update'), ['meta_pixel_id' => self::PIXEL]);
        $this->assertSame('EAABsecret', app(MetaPixel::class)->capiToken());

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertSee('Pixel actif')->assertDontSee('EAABsecret');
    }

    public function test_invalid_pixel_id_is_rejected_and_settings_are_admin_only(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), ['meta_pixel_id' => 'abc'])
            ->assertSessionHasErrors('meta_pixel_id');

        $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_purchase_is_sent_server_side_with_hashed_customer_data(): void
    {
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        Setting::put('meta_pixel_id', self::PIXEL);
        Setting::put('meta_capi_token', 'EAABsecret');
        $book = Book::factory()->create(['price' => 4500]);

        $order = $this->buy($book, 'Awa@Example.com', 'Awa Diallo');

        Http::assertSent(function ($request) use ($order, $book) {
            $event = $request['data'][0];

            return str_starts_with($request->url(), 'https://graph.facebook.com/v25.0/'.self::PIXEL.'/events?access_token=EAABsecret')
                && $event['event_name'] === 'Purchase'
                && $event['event_id'] === 'purchase-'.$order->reference
                && $event['action_source'] === 'website'
                && $event['custom_data']['value'] === 4500
                && $event['custom_data']['currency'] === 'XOF'
                && $event['custom_data']['content_ids'] === [(string) $book->id]
                && $event['user_data']['em'] === [hash('sha256', 'awa@example.com')]
                && $event['user_data']['ph'] === [hash('sha256', '2290197000000')];
        });
    }

    public function test_no_server_call_without_token(): void
    {
        Mail::fake();
        Http::fake();
        Setting::put('meta_pixel_id', self::PIXEL);
        $book = Book::factory()->create();

        $this->buy($book);

        $this->assertSame(1, Order::count());
        Http::assertNothingSent();
    }

    public function test_meta_failure_never_breaks_the_sale(): void
    {
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400)]);
        Setting::put('meta_pixel_id', self::PIXEL);
        Setting::put('meta_capi_token', 'mauvais');
        $book = Book::factory()->create();

        $this->buy($book);

        $this->assertTrue(Order::sole()->isPaid());
    }

    public function test_thanks_page_reports_purchase_with_dedup_event_id(): void
    {
        Mail::fake();
        Setting::put('meta_pixel_id', self::PIXEL);
        $book = Book::factory()->create();
        $order = $this->buy($book);

        $this->get($order->returnUrl())
            ->assertSee("ucTrack('Purchase'", false)
            ->assertSee('purchase-'.$order->reference, false);
    }
}
