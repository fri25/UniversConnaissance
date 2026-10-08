<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Optimise les images déjà en ligne (couvertures, photos d'auteurs, images
 * des descriptions) : redimensionnement + WebP, références mises à jour.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--force : Appliquer réellement (sans cette option : simple aperçu)}';

    protected $description = 'Redimensionne et compresse les images existantes (couvertures, descriptions, auteurs)';

    private int $before = 0;

    private int $after = 0;

    private int $count = 0;

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $disk = Storage::disk('public');
        $this->before = $this->after = $this->count = 0;

        $this->line('Format de sortie : '.(ImageOptimizer::supportsWebp() ? 'WebP' : 'JPEG (WebP non disponible sur ce serveur)'));
        $this->newLine();

        // 1. Couvertures et photos d'auteurs : nouveau fichier + mise à jour de la base.
        foreach (Book::whereNotNull('cover')->get() as $book) {
            $book->cover = $this->convert($book->cover, ImageOptimizer::COVER, $force, $book->title) ?? $book->cover;
            if ($force && $book->isDirty('cover')) {
                $book->saveQuietly();
            }
        }

        foreach (Author::whereNotNull('photo')->get() as $author) {
            $author->photo = $this->convert($author->photo, ImageOptimizer::AVATAR, $force, $author->name) ?? $author->photo;
            if ($force && $author->isDirty('photo')) {
                $author->saveQuietly();
            }
        }

        // 2. Images des descriptions : nouveau fichier + remplacement des liens dans le HTML.
        foreach ($disk->files('descriptions') as $path) {
            $newPath = $this->convert($path, ImageOptimizer::CONTENT, $force, 'description');
            if ($force && $newPath !== null) {
                $this->replaceInDescriptions($path, $newPath);
            }
        }

        $this->newLine();
        if ($this->count === 0) {
            $this->info('Aucune image à optimiser.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d image(s) : %s → %s (−%d %%)',
            $this->count, $this->kb($this->before), $this->kb($this->after),
            $this->before > 0 ? round(100 - $this->after * 100 / $this->before) : 0,
        ));

        if (! $force) {
            $this->warn('Aperçu uniquement : rien n\'a été modifié. Pour appliquer :  php artisan images:optimize --force');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{0: int, 1: int}  $box
     * @return string|null nouveau chemin (ou chemin prévu en aperçu), null si rien à faire
     */
    private function convert(string $path, array $box, bool $force, string $label): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path) || str_ends_with(strtolower($path), '.svg')) {
            return null;
        }

        $original = $disk->get($path);

        // Déjà au format et aux dimensions cibles : ne pas recompresser (perte de qualité à chaque passage).
        $target = ImageOptimizer::supportsWebp() ? 'webp' : 'jpg';
        $size = @getimagesizefromstring($original);
        if ($size && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === $target && $size[0] <= $box[0] && $size[1] <= $box[1]) {
            return null;
        }

        $result = ImageOptimizer::encode($original, $box);

        // Gain négligeable : on laisse l'image telle quelle.
        if ($result === null || strlen($result[0]) > strlen($original) * 0.9) {
            return null;
        }

        [$binary, $extension] = $result;
        $newPath = dirname($path).'/'.Str::random(40).'.'.$extension;

        $this->count++;
        $this->before += strlen($original);
        $this->after += strlen($binary);
        $this->line(sprintf('  %-40s %s → %s', Str::limit($label, 38), $this->kb(strlen($original)), $this->kb(strlen($binary))));

        if ($force) {
            $disk->put($newPath, $binary);
            $disk->delete($path);
        }

        return $newPath;
    }

    private function replaceInDescriptions(string $oldPath, string $newPath): void
    {
        $disk = Storage::disk('public');
        $oldUrl = $disk->url($oldPath);
        $newUrl = $disk->url($newPath);
        $oldRelative = '/storage/'.$oldPath;
        $newRelative = '/storage/'.$newPath;

        Book::where('description', 'like', '%'.basename($oldPath).'%')->get()->each(function (Book $book) use ($oldUrl, $newUrl, $oldRelative, $newRelative) {
            $book->description = str_replace([$oldUrl, $oldRelative], [$newUrl, $newRelative], $book->description);
            $book->saveQuietly();
        });
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' Mo' : round($bytes / 1024).' Ko';
    }
}
