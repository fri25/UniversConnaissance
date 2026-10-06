<x-admin-layout title="Commande {{ $order->reference }}">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Commande <span class="font-mono">{{ $order->reference }}</span></h1>
        <a href="{{ route('admin.orders.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="font-sans font-semibold text-ink dark:text-white">Détail</h2>
            <dl class="mt-4 grid grid-cols-[10rem_1fr] gap-y-2 text-sm">
                <dt class="text-slate-500">Statut</dt><dd><x-order-status :order="$order" /></dd>
                <dt class="text-slate-500">Montant</dt><dd class="font-semibold">{{ fcfa($order->amount) }} ({{ $order->currency }})</dd>
                <dt class="text-slate-500">Prestataire</dt><dd>{{ $order->gateway ?? '—' }}</dd>
                <dt class="text-slate-500">Réf. paiement</dt><dd class="break-all font-mono text-xs">{{ $order->payment_reference ?? '—' }}</dd>
                <dt class="text-slate-500">Créée le</dt><dd>{{ $order->created_at->translatedFormat('d/m/Y H:i') }}</dd>
                <dt class="text-slate-500">Payée le</dt><dd>{{ $order->paid_at?->translatedFormat('d/m/Y H:i') ?? '—' }}</dd>
            </dl>
        </div>

        <div class="card p-5">
            <h2 class="font-sans font-semibold text-ink dark:text-white">Client & e-book</h2>
            <dl class="mt-4 grid grid-cols-[10rem_1fr] gap-y-2 text-sm">
                <dt class="text-slate-500">Client</dt><dd>{{ $order->user->name }}</dd>
                <dt class="text-slate-500">Email</dt><dd>{{ $order->user->email }}</dd>
                <dt class="text-slate-500">Téléphone</dt><dd>{{ $order->user->phone ?? '—' }}</dd>
                <dt class="text-slate-500">E-book</dt><dd><a href="{{ route('admin.books.edit', $order->book) }}" class="link">{{ $order->book->title }}</a></dd>
            </dl>
        </div>

        <div class="card p-5 lg:col-span-2">
            <h2 class="font-sans font-semibold text-ink dark:text-white">Livraison</h2>
            @if ($order->download)
                <dl class="mt-4 grid grid-cols-[10rem_1fr] gap-y-2 text-sm">
                    <dt class="text-slate-500">Téléchargements</dt><dd>{{ $order->download->download_count }} / {{ $order->download->max_downloads }}</dd>
                    <dt class="text-slate-500">Dernier</dt><dd>{{ $order->download->last_downloaded_at?->translatedFormat('d/m/Y H:i') ?? 'jamais' }}</dd>
                    <dt class="text-slate-500">Lien valable jusqu'au</dt><dd>{{ $order->download->expires_at->translatedFormat('d/m/Y H:i') }}</dd>
                </dl>
            @else
                <p class="mt-3 text-sm text-slate-500">Aucun droit de téléchargement (commande non payée).</p>
            @endif
            @if ($order->isPaid())
                <form method="POST" action="{{ route('admin.orders.resend', $order) }}" class="mt-4">
                    @csrf
                    <button class="btn-solid">Renvoyer l'email de livraison</button>
                </form>
            @endif
        </div>
    </div>
</x-admin-layout>
