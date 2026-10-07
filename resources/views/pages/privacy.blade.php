<x-static-page title="Politique de confidentialité">
    <section>
        <h2>Données collectées</h2>
        <ul>
            <li>Compte : nom, adresse email, téléphone (requis pour payer), mot de passe chiffré.</li>
            <li>Commandes : e-books achetés, montants, références de paiement, historique de téléchargement.</li>
            <li>Avis : note et commentaire publiés sous votre nom.</li>
        </ul>
        <p>Nous ne collectons ni ne stockons aucune donnée bancaire ou code Mobile Money : ils sont traités directement par Chariow. Lors d'un achat, nous transmettons à Chariow vos nom, email et téléphone, nécessaires au paiement.</p>
    </section>
    <section>
        <h2>Finalités</h2>
        <ul>
            <li>Gestion du compte, des commandes et de la livraison des e-books.</li>
            <li>Personnalisation des fichiers PDF (filigrane nom/email) pour lutter contre le partage illégal.</li>
            <li>Support client et prévention de la fraude.</li>
        </ul>
    </section>
    <section>
        <h2>Durée de conservation</h2>
        <p>Les données de compte sont conservées tant que le compte est actif. Les données de facturation sont conservées pendant la durée légale applicable.</p>
    </section>
    <section>
        <h2>Vos droits</h2>
        <p>Vous pouvez accéder à vos données, les rectifier ou supprimer votre compte depuis votre profil, ou en écrivant à {{ config('store.support.email') }}.</p>
    </section>
    <section>
        <h2>Cookies et mesure publicitaire</h2>
        <p>Le site utilise des cookies techniques indispensables (session, sécurité CSRF) et le stockage local de votre navigateur pour mémoriser le thème clair/sombre et votre choix concernant les cookies.</p>
        @if (app(\App\Services\MetaPixel::class)->enabled())
            <p>Avec votre accord (bandeau affiché lors de votre première visite), nous utilisons le <strong>pixel Meta</strong> (Facebook / Instagram) pour mesurer l'efficacité de nos publicités : pages consultées, clics sur « Acheter » et achats. Sans votre accord, il n'est pas chargé.</p>
            <p>Lorsqu'un achat est confirmé, nous pouvons transmettre à Meta, via son API Conversions, le montant, l'e-book acheté et vos coordonnées <strong>sous forme chiffrée (hachage SHA-256)</strong> : email, téléphone, prénom. Meta s'en sert uniquement pour rattacher l'achat à une publicité.</p>
            <p><button type="button" class="link" onclick="try{localStorage.removeItem('uc-consent')}catch(e){};location.reload()">Modifier mon choix sur les cookies</button></p>
        @endif
    </section>
</x-static-page>
