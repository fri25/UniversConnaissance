<x-static-page title="Centre d'aide" intro="Les réponses aux questions les plus fréquentes. Une autre question ? Notre équipe est là pour vous.">
    <div class="grid gap-4 sm:grid-cols-2">
        <a href="mailto:{{ config('store.support.email') }}" class="card p-5 transition hover:border-brand-500">
            <p class="text-sm text-slate-500">Écrivez-nous</p>
            <p class="font-semibold text-ink dark:text-white">{{ config('store.support.email') }}</p>
        </a>
        <a href="tel:{{ preg_replace('/\s+/', '', config('store.support.phone')) }}" class="card p-5 transition hover:border-brand-500">
            <p class="text-sm text-slate-500">Appelez-nous · {{ config('store.support.hours') }}</p>
            <p class="font-semibold text-ink dark:text-white">{{ config('store.support.phone') }}</p>
        </a>
    </div>

    <div class="space-y-3">
        @foreach ([
            'Comment acheter un e-book ?' => 'Cliquez sur « Acheter », indiquez votre nom, votre email et votre numéro Mobile Money (aucun compte à créer), puis payez sur la page sécurisée Chariow (Mobile Money ou carte bancaire).',
            'Quand vais-je recevoir mon e-book ?' => 'Immédiatement après la confirmation du paiement : un email contenant votre lien de téléchargement est envoyé à l\'adresse saisie au paiement. Un compte est créé avec cet email : choisissez votre mot de passe (lien dans l\'email) pour retrouver vos e-books dans « Mes achats ».',
            'Quels moyens de paiement acceptez-vous ?' => 'MTN Mobile Money, Moov Money et les principales cartes bancaires, via notre partenaire de paiement Chariow. Nous ne stockons jamais vos données de paiement.',
            'Combien de fois puis-je télécharger mon e-book ?' => 'Chaque achat inclut '.config('ebooks.max_downloads').' téléchargements. Les liens envoyés par email expirent après '.config('ebooks.link_ttl_hours').' heures, mais vous pouvez en générer un nouveau à tout moment depuis « Mes achats ».',
            'Sur quels appareils puis-je lire mes e-books ?' => 'Les PDF s\'ouvrent sur tous les appareils (téléphone, tablette, ordinateur). Les EPUB se lisent avec une liseuse ou une application comme Google Play Livres, Apple Books ou Lithium.',
            'Pourquoi mon nom apparaît-il dans le PDF ?' => 'Chaque PDF est personnalisé par un filigrane discret au nom de l\'acheteur. Cela protège les auteurs contre le partage illégal.',
            'Mon paiement a été débité mais je n\'ai pas mon e-book' => 'La confirmation peut prendre quelques minutes. Si l\'e-book n\'apparaît pas dans « Mes achats » après 30 minutes, contactez-nous avec la référence de votre commande.',
            'Puis-je être remboursé ?' => 'S\'agissant d\'un contenu numérique livré immédiatement, l\'achat est définitif (voir nos CGV). En cas de fichier défectueux, nous vous fournissons un fichier corrigé ou un remboursement.',
        ] as $question => $answer)
            <details class="card group p-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink dark:text-white">
                    {{ $question }}
                    <span class="text-brand-700 transition group-open:rotate-45 dark:text-brand-300" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3">{{ $answer }}</p>
            </details>
        @endforeach
    </div>
</x-static-page>
