<x-admin-layout title="Commandes">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Commandes</h1>
    </x-slot>

    <form class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="q" class="label">Recherche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Référence, client, email…" class="input">
        </div>
        <div>
            <label for="status" class="label">Statut</label>
            <select id="status" name="status" class="input">
                <option value="">Tous</option>
                @foreach (\App\Models\Order::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="label">Du</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
        </div>
        <div>
            <label for="to" class="label">Au</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <button class="btn-solid">Filtrer</button>
            <a href="{{ route('admin.orders.index') }}" class="btn-ghost">Réinitialiser</a>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>Référence</th><th>Client</th><th>E-book</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                @forelse ($orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="link font-mono text-xs">{{ $order->reference }}</a></td>
                        <td>{{ $order->user->name }}<span class="block text-xs text-slate-500">{{ $order->user->email }}</span></td>
                        <td class="max-w-[14rem] truncate">{{ $order->book->title }}</td>
                        <td class="tabular-nums">{{ fcfa($order->amount) }}</td>
                        <td><x-order-status :order="$order" /></td>
                        <td class="whitespace-nowrap">{{ $order->created_at->translatedFormat('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">Aucune commande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-admin-layout>
