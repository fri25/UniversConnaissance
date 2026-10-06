<x-mail::message>
# Merci pour votre achat, {{ $order->user->name }} !

Votre paiement de **{{ fcfa($order->amount) }}** pour **{{ $book->title }}** a bien été confirmé
(commande {{ $order->reference }}).

@foreach ($links as $format => $url)
<x-mail::button :url="$url" color="primary">
Télécharger en {{ $format }}
</x-mail::button>
@endforeach

- Lien valable jusqu'au **{{ $download->expires_at->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}**
- Téléchargements restants : **{{ $download->remaining() }}** / {{ $download->max_downloads }}
- Vous devrez être connecté(e) à votre compte pour télécharger.

Votre e-book est aussi disponible à tout moment dans votre bibliothèque :

<x-mail::button :url="route('dashboard')" color="success">
Ouvrir « Mes achats »
</x-mail::button>

Le fichier est personnalisé à votre nom : merci de ne pas le partager.

Bonne lecture,<br>
L'équipe {{ config('store.name') }}

<x-mail::subcopy>
Besoin d'aide ? Écrivez-nous à {{ config('store.support.email') }} ou appelez le {{ config('store.support.phone') }}.
</x-mail::subcopy>
</x-mail::message>
