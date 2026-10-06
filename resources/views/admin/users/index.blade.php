<x-admin-layout title="Utilisateurs">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Utilisateurs</h1>
    </x-slot>

    <form class="mb-4 flex gap-2">
        <label for="q" class="sr-only">Rechercher</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, email, téléphone…" class="input max-w-xs">
        <button class="btn-outline">Rechercher</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>Utilisateur</th><th>Téléphone</th><th>Achats</th><th>Total dépensé</th><th>Inscrit le</th><th>Rôle</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}<span class="block text-xs text-slate-500">{{ $user->email }}</span></td>
                        <td>{{ $user->phone ?? '—' }}</td>
                        <td>{{ $user->purchases_count }}</td>
                        <td class="tabular-nums">{{ fcfa((int) $user->total_spent) }}</td>
                        <td>{{ $user->created_at->translatedFormat('d/m/Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                @if ($user->is_admin)
                                    <span class="badge-brand">Admin</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-700">Client</span>
                                @endif
                                @unless ($user->is(auth()->user()))
                                    <button class="link text-xs" onclick="return confirm('Changer le rôle de cet utilisateur ?')">{{ $user->is_admin ? 'Retirer admin' : 'Rendre admin' }}</button>
                                @endunless
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-admin-layout>
