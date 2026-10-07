<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Supprime uniquement les données créées par le seeder de démonstration et
 * les paiements simulés, sans toucher aux contenus et ventes réels.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'demo:purge
        {--force : Supprimer réellement (sans cette option : simple aperçu)}
        {--with-categories : Supprimer aussi les catégories de démo restées vides}';

    protected $description = 'Supprime les données de démonstration (e-books, comptes de test, paiements simulés)';

    private const DEMO_ACCOUNTS = ['admin@universconnaissance.test', 'client@universconnaissance.test'];

    public function handle(): int
    {
        $books = Book::whereIn('slug', collect(DemoCatalogSeeder::books())->map(fn ($b) => Str::slug($b['title'])))->get();
        $demoUsers = User::whereIn('email', self::DEMO_ACCOUNTS)->orWhere('email', 'like', '%@exemple.test')->get();

        // Sécurité 1 : ne jamais effacer une vente réelle.
        $realSales = Order::whereIn('book_id', $books->pluck('id'))
            ->where('gateway', '!=', 'fake')
            ->where('status', Order::STATUS_PAID)
            ->with('book:id,title')
            ->get();
        if ($realSales->isNotEmpty()) {
            $this->error('Des e-books de démo ont de vraies ventes : purge annulée pour ne rien perdre.');
            $realSales->each(fn (Order $o) => $this->line("  - {$o->reference} : {$o->book->title}"));

            return self::FAILURE;
        }

        // Sécurité 2 : garder au moins un administrateur.
        $remainingAdmins = User::where('is_admin', true)->whereNotIn('id', $demoUsers->pluck('id'))->count();
        if ($remainingAdmins === 0) {
            $this->error('Le seul administrateur est un compte de démo : vous seriez bloqué hors de l\'admin.');
            $this->line('Créez d\'abord votre propre administrateur :  php artisan uc:make-admin votre@email.com');

            return self::FAILURE;
        }

        // Comptes créés uniquement par des paiements simulés (aucune vente réelle).
        $testGuests = User::where('is_guest', true)
            ->whereNotIn('id', $demoUsers->pluck('id'))
            ->whereHas('orders', fn (Builder $q) => $q->where('gateway', 'fake'))
            ->whereDoesntHave('orders', fn (Builder $q) => $q->where('gateway', '!=', 'fake'))
            ->get();
        $users = $demoUsers->merge($testGuests);

        $orders = Order::where('gateway', 'fake')
            ->orWhereIn('book_id', $books->pluck('id'))
            ->orWhereIn('user_id', $users->pluck('id'))
            ->get();

        $authors = Author::whereIn('slug', collect(array_keys(DemoCatalogSeeder::AUTHORS))->map(fn ($n) => Str::slug($n)))
            ->whereDoesntHave('books', fn (Builder $q) => $q->whereNotIn('books.id', $books->pluck('id')))
            ->get();

        $categories = $this->option('with-categories')
            ? Category::whereIn('slug', array_keys(DemoCatalogSeeder::CATEGORIES))
                ->whereDoesntHave('books', fn (Builder $q) => $q->whereNotIn('books.id', $books->pluck('id')))
                ->get()
            : collect();

        $this->summary($books, $users, $orders, $authors, $categories);

        if (! $this->option('force')) {
            $this->newLine();
            $this->warn('Aperçu uniquement : rien n\'a été supprimé.');
            $this->line('Pour supprimer réellement :  php artisan demo:purge --force'.($this->option('with-categories') ? ' --with-categories' : ''));

            return self::SUCCESS;
        }

        $files = $books->flatMap(fn (Book $b) => [
            [config('ebooks.disk'), $b->file_path], [config('ebooks.disk'), $b->epub_path],
            [config('ebooks.disk'), $b->sample_path], ['public', $b->cover],
        ])->filter(fn ($f) => $f[1]);

        DB::transaction(function () use ($orders, $users, $books, $authors, $categories) {
            // Les téléchargements et avis suivent par suppression en cascade.
            Order::whereIn('id', $orders->pluck('id'))->delete();
            User::whereIn('id', $users->pluck('id'))->delete();
            Book::whereIn('id', $books->pluck('id'))->delete();
            Author::whereIn('id', $authors->pluck('id'))->delete();
            Category::whereIn('id', $categories->pluck('id'))->delete();
        });

        $files->each(fn ($f) => Storage::disk($f[0])->delete($f[1]));

        $this->newLine();
        $this->info('✓ Données de démonstration supprimées.');

        return self::SUCCESS;
    }

    private function summary(Collection $books, Collection $users, Collection $orders, Collection $authors, Collection $categories): void
    {
        $this->info('Éléments de démonstration trouvés :');
        $this->table(['Type', 'Nombre', 'Détail'], [
            ['E-books (+ fichiers)', $books->count(), Str::limit($books->pluck('title')->join(', '), 90)],
            ['Comptes', $users->count(), Str::limit($users->pluck('email')->join(', '), 90)],
            ['Commandes simulées / de démo', $orders->count(), ''],
            ['Auteurs sans autre livre', $authors->count(), Str::limit($authors->pluck('name')->join(', '), 90)],
            ['Catégories', $categories->count(), $this->option('with-categories') ? $categories->pluck('name')->join(', ') : 'conservées (option --with-categories pour les retirer)'],
        ]);
    }
}
