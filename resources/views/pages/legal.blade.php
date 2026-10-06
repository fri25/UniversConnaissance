<x-static-page title="Mentions légales">
    <section>
        <h2>Éditeur du site</h2>
        <p>{{ config('store.name') }} — [Raison sociale, forme juridique, capital]<br>
            Siège social : [adresse complète]<br>
            RCCM : [numéro] · IFU : [numéro]<br>
            Directeur de la publication : [nom]<br>
            Contact : {{ config('store.support.email') }} · {{ config('store.support.phone') }}</p>
    </section>
    <section>
        <h2>Hébergement</h2>
        <p>[Nom de l'hébergeur, adresse, téléphone]</p>
    </section>
    <section>
        <h2>Propriété intellectuelle</h2>
        <p>Les e-books proposés sont protégés par le droit d'auteur. Leur achat confère une licence d'usage strictement personnelle et non cessible. Toute reproduction, diffusion ou revente, totale ou partielle, est interdite sans l'autorisation des ayants droit.</p>
        <p>Les éléments du site (logo, textes, charte graphique) sont la propriété de {{ config('store.name') }}.</p>
    </section>
    <section>
        <h2>Paiements</h2>
        <p>Les paiements sont traités par Chariow. {{ config('store.name') }} n'a jamais accès à vos coordonnées bancaires ni à vos codes Mobile Money.</p>
    </section>
</x-static-page>
