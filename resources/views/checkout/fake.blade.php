<x-app-layout title="Paiement simulé">
    <div class="container-page max-w-lg py-12">
        <div class="card p-6 text-center">
            <span class="badge bg-amber-100 text-amber-900">Mode démonstration</span>
            <h1 class="mt-4 text-2xl font-bold text-ink dark:text-white">Paiement simulé</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">
                Commande <strong>{{ $order->reference }}</strong> — {{ $order->book->title }}<br>
                Montant : <strong>{{ fcfa($order->amount) }}</strong>
            </p>
            <p class="mt-4 text-sm text-slate-500">
                En production, cette étape est remplacée par la page de paiement Chariow (Mobile Money / carte).
            </p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <form method="POST" action="{{ route('payment.fake.complete', $order) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="failure">
                    <button class="btn-outline w-full">Simuler un échec</button>
                </form>
                <form method="POST" action="{{ route('payment.fake.complete', $order) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="success">
                    <button class="btn-primary w-full">Confirmer le paiement</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
