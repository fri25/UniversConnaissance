<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    /**
     * Image « photo » volumineuse (bruit = difficile à compresser, comme une vraie couverture).
     */
    private function heavyPng(int $width = 1600, int $height = 2400): string
    {
        mt_srand(42);
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y += 4) {
            for ($x = 0; $x < $width; $x += 4) {
                // Dégradé + bruit : se compresse mal en PNG, comme une photo.
                imagefilledrectangle($img, $x, $y, $x + 3, $y + 3, imagecolorallocate(
                    $img,
                    min(255, (int) ($x / $width * 200) + mt_rand(0, 55)),
                    min(255, (int) ($y / $height * 200) + mt_rand(0, 55)),
                    mt_rand(80, 200),
                ));
            }
        }
        mt_srand();
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }

    public function test_uploaded_cover_is_resized_and_compressed(): void
    {
        $png = $this->heavyPng();
        $book = Book::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.books.update', $book), [
            'title' => $book->title, 'language' => 'fr', 'format' => 'pdf', 'price' => 1000,
            'cover' => UploadedFile::fake()->createWithContent('couverture.png', $png),
        ])->assertRedirect();

        $path = $book->fresh()->cover;
        $this->assertStringEndsWith(ImageOptimizer::supportsWebp() ? '.webp' : '.jpg', $path);

        $stored = Storage::disk('public')->get($path);
        [$width, $height] = getimagesizefromstring($stored);
        $this->assertSame([600, 900], [$width, $height]);
        $this->assertLessThan(strlen($png) / 5, strlen($stored), 'Au moins 5 fois plus léger');
    }

    public function test_small_images_are_not_enlarged(): void
    {
        $result = ImageOptimizer::encode($this->heavyPng(300, 450), ImageOptimizer::COVER);

        [$width, $height] = getimagesizefromstring($result[0]);
        $this->assertSame([300, 450], [$width, $height]);
    }

    public function test_editor_upload_is_optimized(): void
    {
        $url = $this->actingAs($this->admin)
            ->postJson(route('admin.editor.images'), ['image' => UploadedFile::fake()->createWithContent('capture.png', $this->heavyPng(2400, 1200))])
            ->assertOk()
            ->json('url');

        $file = Storage::disk('public')->files('descriptions')[0];
        $this->assertStringEndsWith(basename($file), $url);
        [$width] = getimagesizefromstring(Storage::disk('public')->get($file));
        $this->assertSame(1600, $width);
    }

    public function test_command_previews_then_optimizes_existing_images(): void
    {
        $disk = Storage::disk('public');
        $disk->put('covers/ancienne.png', $this->heavyPng());
        $disk->put('descriptions/photo.png', $this->heavyPng(2000, 1000));
        $book = Book::factory()->create([
            'cover' => 'covers/ancienne.png',
            'description' => '<p>Voir :</p><p><img src="'.$disk->url('descriptions/photo.png').'"></p>',
        ]);
        $svg = Book::factory()->create(['cover' => 'covers/demo.svg']);
        $disk->put('covers/demo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->artisan('images:optimize')->expectsOutputToContain('Aperçu uniquement')->assertSuccessful();
        $this->assertSame('covers/ancienne.png', $book->fresh()->cover);

        $this->artisan('images:optimize --force')->assertSuccessful();

        $book->refresh();
        $this->assertNotSame('covers/ancienne.png', $book->cover);
        $disk->assertMissing('covers/ancienne.png');
        $disk->assertExists($book->cover);
        $this->assertSame([600, 900], array_slice(getimagesizefromstring($disk->get($book->cover)), 0, 2));

        $disk->assertMissing('descriptions/photo.png');
        $this->assertStringNotContainsString('photo.png', $book->description);
        preg_match('#/storage/(descriptions/[^"]+)#', $book->description, $m);
        $disk->assertExists($m[1]);

        $this->assertSame('covers/demo.svg', $svg->fresh()->cover, 'SVG laissés tels quels');

        // Relancer ne refait rien.
        $this->artisan('images:optimize --force')->expectsOutputToContain('Aucune image à optimiser')->assertSuccessful();
    }

    public function test_animated_gif_is_kept_as_is(): void
    {
        $frame = base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');
        $animated = str_replace('!', "\x21\xF9\x04", 'GIF89a').substr($frame, 6)."\x21\xF9\x04".substr($frame, 6);

        $this->assertNull(ImageOptimizer::encode($animated, ImageOptimizer::CONTENT));
    }
}
