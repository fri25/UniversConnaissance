<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Services\DownloadService;
use App\Support\DemoEbookGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $pdf = (new DemoEbookGenerator)->pdf('Livre test', 'Auteur', 'Ouverture.', ['Chapitre 1']);
        Storage::disk('local')->put('ebooks/livre-test.pdf', $pdf);
        Storage::disk('local')->put('samples/livre-test-extrait.pdf', $pdf);

        $book = Book::factory()->create([
            'title' => 'Livre test',
            'slug' => 'livre-test',
            'file_path' => 'ebooks/livre-test.pdf',
            'sample_path' => 'samples/livre-test-extrait.pdf',
        ]);
        $this->order = Order::factory()->paid()->for($book)->create();
        app(DownloadService::class)->issue($this->order);
    }

    private function signedUrl(string $format = 'pdf'): string
    {
        return app(DownloadService::class)->signedUrl($this->order->download()->first(), $format);
    }

    public function test_owner_downloads_watermarked_pdf(): void
    {
        $response = $this->actingAs($this->order->user)->get($this->signedUrl());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="livre-test.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        // Le filigrane est ajouté : le fichier servi diffère de l'original.
        $this->assertNotSame(Storage::disk('local')->get('ebooks/livre-test.pdf'), $response->getContent());
        $this->assertSame(1, $this->order->download()->first()->download_count);
    }

    public function test_library_route_redirects_to_signed_url(): void
    {
        $this->actingAs($this->order->user)
            ->get(route('library.download', [$this->order, 'pdf']))
            ->assertRedirectContains('/telecharger/')
            ->assertRedirectContains('signature=');
    }

    public function test_unsigned_or_tampered_url_is_rejected(): void
    {
        $token = $this->order->download()->first()->token;

        $this->actingAs($this->order->user)->get("/telecharger/{$token}/pdf")->assertForbidden();
        $this->actingAs($this->order->user)->get($this->signedUrl().'x')->assertForbidden();
    }

    public function test_other_user_cannot_download_even_with_signed_url(): void
    {
        $this->actingAs(User::factory()->create())->get($this->signedUrl())->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('library.download', [$this->order, 'pdf']))->assertForbidden();
    }

    public function test_guest_must_log_in(): void
    {
        $this->get($this->signedUrl())->assertRedirect('/login');
    }

    public function test_download_limit_is_enforced(): void
    {
        $this->order->download->update(['download_count' => $this->order->download->max_downloads]);

        $this->actingAs($this->order->user)->get($this->signedUrl())->assertStatus(429);
        $this->actingAs($this->order->user)
            ->from('/dashboard')
            ->get(route('library.download', [$this->order, 'pdf']))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error');
    }

    public function test_expired_token_is_refused_but_library_renews_it(): void
    {
        $download = $this->order->download;
        $download->forceFill(['expires_at' => now()->addMinute()])->save();
        $url = $this->signedUrl();
        $download->forceFill(['expires_at' => now()->subMinute()])->save();
        $oldToken = $download->token;

        $this->actingAs($this->order->user)->get($url)->assertStatus(410);

        $this->actingAs($this->order->user)->get(route('library.download', [$this->order, 'pdf']))->assertRedirect();
        $this->assertNotSame($oldToken, $download->fresh()->token);
        $this->assertTrue($download->fresh()->expires_at->isFuture());
    }

    public function test_refunded_order_loses_access(): void
    {
        $url = $this->signedUrl();
        $this->order->update(['status' => Order::STATUS_REFUNDED]);

        $this->actingAs($this->order->user)->get($url)->assertForbidden();
    }

    public function test_unavailable_format_returns_404(): void
    {
        $this->actingAs($this->order->user)->get(route('library.download', [$this->order, 'epub']))->assertNotFound();
    }

    public function test_free_sample_is_downloadable_without_purchase(): void
    {
        $this->get(route('books.sample', $this->order->book))
            ->assertOk()
            ->assertDownload('livre-test-extrait.pdf');
    }

    public function test_full_file_is_never_publicly_exposed(): void
    {
        $this->get('/storage/ebooks/livre-test.pdf')->assertNotFound();
        $this->assertStringNotContainsString('ebooks/livre-test.pdf', $this->get(route('books.show', $this->order->book))->getContent());
    }
}
