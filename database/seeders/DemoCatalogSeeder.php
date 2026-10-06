<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Download;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Support\CoverGenerator;
use App\Support\DemoEbookGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    private const CATEGORIES = [
        'litterature' => ['Littérature', 'Romans, contes et grands classiques de la littérature francophone.'],
        'developpement-personnel' => ['Développement personnel', 'Habitudes, confiance en soi et productivité pour progresser chaque jour.'],
        'business' => ['Business', 'Entrepreneuriat, finance et stratégie pour bâtir et faire grandir votre activité.'],
        'sciences' => ['Sciences', 'Comprendre le monde : astronomie, physique, nature et découvertes.'],
        'education' => ['Éducation', 'Méthodes d\'apprentissage, philosophie et réussite scolaire.'],
    ];

    private const AUTHORS = [
        'Victor Hugo' => 'Poète, dramaturge et romancier (1802-1885), figure majeure du romantisme français.',
        'Alexandre Dumas' => 'Romancier (1802-1870), maître du roman d\'aventures historique.',
        'Gustave Flaubert' => 'Romancier (1821-1880), célèbre pour son style d\'une précision remarquable.',
        'Voltaire' => 'Écrivain et philosophe des Lumières (1694-1778), maître de l\'ironie.',
        'René Descartes' => 'Philosophe et mathématicien (1596-1650), père du rationalisme moderne.',
        'Jules Verne' => 'Romancier (1828-1905), pionnier du roman d\'anticipation scientifique.',
        'Awa Diallo' => 'Coach et conférencière, elle accompagne depuis 15 ans les jeunes professionnels vers leurs objectifs.',
        'Koffi Mensah' => 'Entrepreneur et investisseur, fondateur de plusieurs startups fintech en Afrique de l\'Ouest.',
        'Fatou Ndiaye' => 'Astrophysicienne et vulgarisatrice scientifique passionnée.',
        'Jean-Baptiste Houngbo' => 'Professeur agrégé, spécialiste des méthodes d\'apprentissage.',
    ];

    public function run(): void
    {
        $covers = new CoverGenerator;
        $files = new DemoEbookGenerator;
        $private = Storage::disk(config('ebooks.disk'));
        $public = Storage::disk('public');

        // --- Comptes -----------------------------------------------------------
        $admin = User::updateOrCreate(['email' => 'admin@universconnaissance.test'], [
            'name' => 'Administrateur UC',
            'phone' => '+229 01 00 00 00 01',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $client = User::updateOrCreate(['email' => 'client@universconnaissance.test'], [
            'name' => 'Aïcha Lecteur',
            'phone' => '01 97 00 00 00',
            'phone_country' => 'BJ',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $readers = collect(['Moussa Traoré', 'Grâce Adjovi', 'Ibrahim Sow', 'Mariam Koné', 'Paul Agbossou', 'Nadia Bello'])
            ->map(fn (string $name) => User::updateOrCreate(
                ['email' => Str::slug($name, '.').'@exemple.test'],
                ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now()],
            ));

        // --- Catégories & auteurs --------------------------------------------
        $categories = collect(self::CATEGORIES)->map(fn ($data, $slug) => Category::updateOrCreate(
            ['slug' => $slug],
            ['name' => $data[0], 'description' => $data[1]],
        ));

        $authors = collect(self::AUTHORS)->map(fn ($bio, $name) => Author::updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'bio' => $bio],
        ));

        // --- E-books -------------------------------------------------------------
        foreach ($this->books() as $i => $data) {
            $slug = Str::slug($data['title']);
            $authorName = $data['author'];
            $category = $categories[$data['categories'][0]];

            $coverPath = "covers/{$slug}.svg";
            $public->put($coverPath, $covers->svg($data['title'], $authorName, $category->name, $data['categories'][0], $i));

            $chapters = $data['chapters'];
            $paths = ['file_path' => null, 'epub_path' => null];

            if (in_array($data['format'], ['pdf', 'both'], true)) {
                $paths['file_path'] = "ebooks/{$slug}.pdf";
                $private->put($paths['file_path'], $files->pdf($data['title'], $authorName, $data['opening'], $chapters));
            }
            if (in_array($data['format'], ['epub', 'both'], true)) {
                $key = $data['format'] === 'epub' ? 'file_path' : 'epub_path';
                $paths[$key] = "ebooks/{$slug}.epub";
                $private->put($paths[$key], $files->epub($data['title'], $authorName, $data['opening'], $chapters));
            }

            $samplePath = "samples/{$slug}-extrait.pdf";
            $private->put($samplePath, $files->pdf($data['title'], $authorName, $data['opening'], $chapters, sample: true));

            $book = Book::updateOrCreate(['slug' => $slug], [
                'title' => $data['title'],
                'summary' => $data['summary'],
                'table_of_contents' => implode("\n", $chapters),
                'isbn' => $data['isbn'] ?? null,
                'publisher' => $data['publisher'],
                'published_year' => $data['year'],
                'language' => 'fr',
                'pages' => $data['pages'],
                'format' => $data['format'],
                'price' => $data['price'],
                'old_price' => $data['old_price'] ?? null,
                'cover' => $coverPath,
                'sample_path' => $samplePath,
                'file_path' => $paths['file_path'],
                'epub_path' => $paths['epub_path'],
                'file_size' => $private->size($paths['file_path']),
                'is_active' => true,
                'is_featured' => $data['featured'] ?? false,
            ]);
            // Dates de mise en ligne étalées : certaines « nouveautés », d'autres plus anciennes.
            $book->forceFill(['created_at' => now()->subDays($data['age_days'])])->saveQuietly();

            $book->authors()->sync([$authors[$authorName]->id]);
            $book->categories()->sync($categories->only($data['categories'])->pluck('id')->all());
        }

        // --- Ventes & avis de démonstration ----------------------------------------
        if (Order::exists()) {
            return;
        }

        $books = Book::all()->keyBy('title');
        $comments = [
            5 => ['Un vrai régal, je recommande !', 'Téléchargement immédiat et lecture parfaite sur mon téléphone.', 'Indispensable.'],
            4 => ['Très bon livre, quelques passages un peu longs.', 'Belle découverte, bien écrit.'],
            3 => ['Intéressant mais j\'en attendais un peu plus.'],
        ];

        $purchase = function (User $user, Book $book, int $daysAgo) {
            $order = Order::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'amount' => $book->price,
                'currency' => 'XOF',
                'status' => Order::STATUS_PAID,
                'gateway' => 'fake',
                'payment_reference' => 'demo_'.Str::lower(Str::random(12)),
                'paid_at' => now()->subDays($daysAgo)->setTime(rand(8, 21), rand(0, 59)),
            ]);
            Download::create([
                'order_id' => $order->id,
                'token' => Download::newToken(),
                'expires_at' => now()->addHours(config('ebooks.link_ttl_hours')),
                'max_downloads' => config('ebooks.max_downloads'),
                'download_count' => rand(0, 2),
            ]);

            return $order;
        };

        // Le client de test possède deux e-books.
        $purchase($client, $books["L'Art de l'habitude"], 3);
        $purchase($client, $books['Les Misérables — Tome 1 : Fantine'], 12);

        mt_srand(42);
        foreach ($readers as $r => $reader) {
            foreach ($books->values()->shuffle(crc32($reader->email))->take(4 + $r % 3) as $book) {
                $purchase($reader, $book, mt_rand(0, 29));
                if (mt_rand(0, 10) > 3) {
                    $rating = [5, 5, 4, 4, 3][mt_rand(0, 4)];
                    Review::create([
                        'user_id' => $reader->id,
                        'book_id' => $book->id,
                        'rating' => $rating,
                        'comment' => $comments[$rating][mt_rand(0, count($comments[$rating]) - 1)],
                    ]);
                }
            }
        }
        mt_srand();

        // Une commande en attente et une échouée pour l'admin.
        Order::create(['user_id' => $readers[0]->id, 'book_id' => $books['Oser entreprendre en Afrique']->id, 'amount' => $books['Oser entreprendre en Afrique']->price, 'status' => Order::STATUS_PENDING, 'gateway' => 'fake']);
        Order::create(['user_id' => $readers[1]->id, 'book_id' => $books['Vingt mille lieues sous les mers']->id, 'amount' => $books['Vingt mille lieues sous les mers']->price, 'status' => Order::STATUS_FAILED, 'gateway' => 'fake']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function books(): array
    {
        return [
            [
                'title' => 'Les Misérables — Tome 1 : Fantine', 'author' => 'Victor Hugo', 'categories' => ['litterature'],
                'publisher' => 'Domaine public', 'year' => 1862, 'pages' => 512, 'format' => 'both', 'price' => 2500, 'old_price' => 3500, 'age_days' => 120, 'featured' => true,
                'opening' => 'En 1815, M. Charles-François-Bienvenu Myriel était évêque de Digne.',
                'summary' => "Jean Valjean, ancien forçat, croise la route de l'évêque Myriel dont la bonté va bouleverser sa vie. Premier tome de la fresque monumentale de Victor Hugo, Fantine dresse le portrait d'une société où la misère broie les plus faibles.\n\nUn chef-d'œuvre intemporel sur la justice, la rédemption et la compassion.",
                'chapters' => ['Un juste', 'La chute', 'En l\'année 1817', 'Confier, c\'est quelquefois livrer', 'La descente'],
            ],
            [
                'title' => 'Le Comte de Monte-Cristo', 'author' => 'Alexandre Dumas', 'categories' => ['litterature'],
                'publisher' => 'Domaine public', 'year' => 1844, 'pages' => 640, 'format' => 'epub', 'price' => 3000, 'age_days' => 95,
                'opening' => 'Le 24 février 1815, la vigie de Notre-Dame de la Garde signala le trois-mâts le Pharaon, venant de Smyrne, Trieste et Naples.',
                'summary' => "Edmond Dantès, jeune marin promis à un brillant avenir, est trahi le jour de ses fiançailles et jeté au château d'If. Quatorze ans plus tard, il s'évade, riche d'un trésor fabuleux, et prépare une vengeance implacable.\n\nLe plus grand roman d'aventures de la littérature française.",
                'chapters' => ['Marseille. L\'arrivée', 'Le père et le fils', 'Les Catalans', 'Complot', 'Le château d\'If'],
            ],
            [
                'title' => 'Madame Bovary', 'author' => 'Gustave Flaubert', 'categories' => ['litterature'],
                'publisher' => 'Domaine public', 'year' => 1857, 'pages' => 380, 'format' => 'pdf', 'price' => 2000, 'age_days' => 200,
                'opening' => 'Nous étions à l\'Étude, quand le Proviseur entra, suivi d\'un nouveau habillé en bourgeois et d\'un garçon de classe qui portait un grand pupitre.',
                'summary' => "Emma, épouse d'un médecin de campagne, rêve d'une vie de passion et de luxe nourrie par ses lectures romantiques. La réalité de la province normande se révèle bien différente…\n\nUn roman fondateur du réalisme.",
                'chapters' => ['Charles', 'Les Bertaux', 'Tostes', 'Le bal de la Vaubyessard', 'Yonville-l\'Abbaye'],
            ],
            [
                'title' => 'Candide ou l\'Optimisme', 'author' => 'Voltaire', 'categories' => ['litterature', 'education'],
                'publisher' => 'Domaine public', 'year' => 1759, 'pages' => 160, 'format' => 'both', 'price' => 1500, 'age_days' => 15,
                'opening' => 'Il y avait en Westphalie, dans le château de M. le baron de Thunder-ten-tronckh, un jeune garçon à qui la nature avait donné les mœurs les plus douces.',
                'summary' => "Chassé du « plus beau des châteaux », le naïf Candide parcourt le monde et affronte guerres, naufrages et injustices, tout en restant fidèle à la doctrine de son maître Pangloss : tout est pour le mieux dans le meilleur des mondes possibles.\n\nUn conte philosophique mordant et toujours actuel.",
                'chapters' => ['Comment Candide fut élevé', 'Ce que devint Candide parmi les Bulgares', 'Comment Candide se sauva', 'L\'Eldorado', 'Il faut cultiver notre jardin'],
            ],
            [
                'title' => 'Discours de la méthode', 'author' => 'René Descartes', 'categories' => ['education', 'sciences'],
                'publisher' => 'Domaine public', 'year' => 1637, 'pages' => 96, 'format' => 'pdf', 'price' => 1500, 'age_days' => 160,
                'opening' => 'Le bon sens est la chose du monde la mieux partagée : car chacun pense en être si bien pourvu, que ceux même qui sont les plus difficiles à contenter en toute autre chose n\'ont point coutume d\'en désirer plus qu\'ils en ont.',
                'summary' => "« Pour bien conduire sa raison et chercher la vérité dans les sciences. » Descartes y expose les quatre règles de sa méthode et pose les bases de la pensée moderne.\n\nUn texte court, essentiel pour tout étudiant.",
                'chapters' => ['Considérations touchant les sciences', 'Principales règles de la méthode', 'Quelques règles de la morale', 'Je pense, donc je suis', 'L\'ordre des questions de physique'],
            ],
            [
                'title' => 'Vingt mille lieues sous les mers', 'author' => 'Jules Verne', 'categories' => ['litterature', 'sciences'],
                'publisher' => 'Domaine public', 'year' => 1870, 'pages' => 450, 'format' => 'epub', 'price' => 2500, 'old_price' => 3000, 'age_days' => 70,
                'opening' => 'L\'année 1866 fut marquée par un événement bizarre, un phénomène inexpliqué et inexplicable que personne n\'a sans doute oublié.',
                'summary' => "Le professeur Aronnax part à la chasse d'un mystérieux monstre marin… qui se révèle être le Nautilus, sous-marin du capitaine Nemo. Commence alors un extraordinaire voyage au fond des océans.\n\nAventure et science se rencontrent dans ce classique de Jules Verne.",
                'chapters' => ['Un écueil fuyant', 'Le pour et le contre', 'Mobilis in mobili', 'L\'Atlantide', 'Le Maelstrom'],
            ],
            [
                'title' => 'L\'Art de l\'habitude', 'author' => 'Awa Diallo', 'categories' => ['developpement-personnel'],
                'publisher' => 'UC Éditions', 'year' => 2025, 'pages' => 210, 'format' => 'pdf', 'price' => 4500, 'old_price' => 6000, 'age_days' => 5, 'featured' => true,
                'isbn' => '978-2-9600001-0-1',
                'opening' => 'Nous ne devenons pas ce que nous voulons, nous devenons ce que nous répétons.',
                'summary' => "Pourquoi certaines résolutions tiennent-elles et d'autres s'effondrent-elles en une semaine ? Awa Diallo décortique la mécanique des habitudes et propose une méthode simple en 21 jours pour installer durablement les routines qui comptent.\n\nExercices pratiques, témoignages et fiches à imprimer inclus.",
                'chapters' => ['Le pouvoir des petites actions', 'Comprendre la boucle de l\'habitude', 'Concevoir son environnement', 'Le défi des 21 jours', 'Rebondir après un écart'],
            ],
            [
                'title' => 'Le Pouvoir de la discipline', 'author' => 'Awa Diallo', 'categories' => ['developpement-personnel'],
                'publisher' => 'UC Éditions', 'year' => 2024, 'pages' => 180, 'format' => 'both', 'price' => 4000, 'age_days' => 45,
                'isbn' => '978-2-9600001-1-8',
                'opening' => 'La motivation vous fait commencer ; la discipline vous fait continuer.',
                'summary' => "La discipline n'est pas une punition : c'est une forme de liberté. Ce guide pratique vous aide à clarifier vos priorités, organiser vos journées et tenir vos engagements envers vous-même.",
                'chapters' => ['Discipline et liberté', 'Clarifier ses priorités', 'La journée idéale', 'Gérer les distractions', 'Tenir sur la durée'],
            ],
            [
                'title' => 'Oser entreprendre en Afrique', 'author' => 'Koffi Mensah', 'categories' => ['business'],
                'publisher' => 'UC Éditions', 'year' => 2025, 'pages' => 260, 'format' => 'pdf', 'price' => 6500, 'age_days' => 10, 'featured' => true,
                'isbn' => '978-2-9600001-2-5',
                'opening' => 'Le meilleur moment pour lancer son entreprise était il y a dix ans. Le deuxième meilleur moment, c\'est aujourd\'hui.',
                'summary' => "De l'idée au premier client, Koffi Mensah partage son expérience d'entrepreneur en Afrique de l'Ouest : valider un marché, financer son projet sans se ruiner, recruter, et surmonter les obstacles administratifs.\n\nUn manuel concret, illustré de cas réels.",
                'chapters' => ['Trouver la bonne idée', 'Tester son marché', 'Financer son projet', 'Bâtir son équipe', 'Passer à l\'échelle'],
            ],
            [
                'title' => 'Mobile Money : la révolution des paiements', 'author' => 'Koffi Mensah', 'categories' => ['business'],
                'publisher' => 'UC Éditions', 'year' => 2024, 'pages' => 190, 'format' => 'epub', 'price' => 5000, 'old_price' => 7500, 'age_days' => 60,
                'opening' => 'En moins de quinze ans, le téléphone est devenu la première banque du continent.',
                'summary' => "Comment le Mobile Money a-t-il transformé l'économie africaine ? Histoire, acteurs, modèles économiques et opportunités pour les entrepreneurs et les commerçants.",
                'chapters' => ['Aux origines du Mobile Money', 'Les grands acteurs', 'Modèles économiques', 'Inclusion financière', 'Et demain ?'],
            ],
            [
                'title' => 'Comprendre l\'Univers en 10 leçons', 'author' => 'Fatou Ndiaye', 'categories' => ['sciences'],
                'publisher' => 'UC Éditions', 'year' => 2025, 'pages' => 230, 'format' => 'pdf', 'price' => 5500, 'age_days' => 2,
                'isbn' => '978-2-9600001-3-2',
                'opening' => 'Levez les yeux : la lumière des étoiles que vous voyez ce soir est partie bien avant votre naissance.',
                'summary' => "Big Bang, trous noirs, exoplanètes, matière noire… L'astrophysicienne Fatou Ndiaye rend accessibles les grandes questions de l'astronomie moderne, sans équations et avec passion.",
                'chapters' => ['Le ciel à l\'œil nu', 'La lumière, messagère du cosmos', 'Naissance et mort des étoiles', 'Les trous noirs', 'Sommes-nous seuls ?'],
            ],
            [
                'title' => 'Réussir ses examens', 'author' => 'Jean-Baptiste Houngbo', 'categories' => ['education'],
                'publisher' => 'UC Éditions', 'year' => 2023, 'pages' => 150, 'format' => 'pdf', 'price' => 3500, 'age_days' => 140,
                'isbn' => '978-2-9600001-4-9',
                'opening' => 'Apprendre n\'est pas une question de talent, mais de méthode.',
                'summary' => 'Mémorisation active, planning de révisions, gestion du stress, techniques de dissertation : toutes les clés pour aborder le BEPC, le BAC et les examens universitaires avec confiance.',
                'chapters' => ['Comment fonctionne la mémoire', 'Planifier ses révisions', 'Les fiches efficaces', 'Le jour J', 'Après l\'examen'],
            ],
        ];
    }
}
