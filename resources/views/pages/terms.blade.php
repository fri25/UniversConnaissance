<x-static-page title="Conditions générales de vente" intro="Les présentes conditions régissent la vente d'e-books sur {{ config('store.name') }}.">
    <section>
        <h2>1. Objet</h2>
        <p>{{ config('store.name') }} commercialise exclusivement des livres numériques (e-books) aux formats PDF et/ou EPUB. Aucun livre physique n'est vendu ni expédié.</p>
    </section>
    <section>
        <h2>2. Prix</h2>
        <p>Les prix sont indiqués en francs CFA (XOF), toutes taxes comprises. Le prix applicable est celui affiché au moment de la validation de la commande.</p>
    </section>
    <section>
        <h2>3. Commande et paiement</h2>
        <p>La commande nécessite un compte client. Le paiement s'effectue en ligne via Chariow (Mobile Money, carte bancaire). La commande est considérée comme ferme dès la confirmation du paiement par le prestataire.</p>
    </section>
    <section>
        <h2>4. Livraison</h2>
        <p>L'e-book est livré immédiatement après confirmation du paiement : il est ajouté à l'espace « Mes achats » et un lien de téléchargement est envoyé par email.</p>
        <ul>
            <li>Nombre de téléchargements inclus : {{ config('ebooks.max_downloads') }} par achat.</li>
            <li>Les liens envoyés par email sont valables {{ config('ebooks.link_ttl_hours') }} heures et peuvent être régénérés depuis « Mes achats ».</li>
        </ul>
    </section>
    <section>
        <h2>5. Licence d'utilisation</h2>
        <p>L'achat confère une licence personnelle, non exclusive et non transférable. Les fichiers PDF comportent un filigrane identifiant l'acheteur. Le partage, la revente ou la diffusion des fichiers sont interdits et peuvent entraîner la suspension du compte.</p>
    </section>
    <section>
        <h2>6. Droit de rétractation</h2>
        <p>Conformément aux usages applicables aux contenus numériques fournis sur un support immatériel, le client reconnaît que l'exécution commence dès la confirmation du paiement et renonce expressément à son droit de rétractation.</p>
    </section>
    <section>
        <h2>7. Réclamations</h2>
        <p>Pour tout fichier défectueux ou problème de téléchargement, contactez {{ config('store.support.email') }}. Nous fournirons un fichier de remplacement ou, à défaut, un remboursement.</p>
    </section>
</x-static-page>
