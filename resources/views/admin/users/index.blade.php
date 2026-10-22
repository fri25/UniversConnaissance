<x-admin-layout title="Utilisateurs">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink ">Utilisateurs</h1>
    </x-slot>

    <form method="POST" action="{{ route('admin.users.store') }}" class="card mb-6 space-y-4 p-5">
        @csrf
        <h2 class="text-lg font-semibold text-ink ">Créer un utilisateur</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="user-name" class="label">Nom</label>
                <input id="user-name" name="name" type="text" value="{{ old('name') }}" maxlength="255" class="input mt-1 w-full" required autocomplete="name">
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="user-email" class="label">Adresse e-mail</label>
                <input id="user-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" class="input mt-1 w-full" required autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="user-phone" class="label">Téléphone (facultatif)</label>
                <input id="user-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" class="input mt-1 w-full" autocomplete="tel">
                @error('phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="user-password" class="label">Mot de passe temporaire</label>
                <input id="user-password" name="password" type="password" class="input mt-1 w-full" required autocomplete="new-password">
                @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="user-password-confirmation" class="label">Confirmer le mot de passe</label>
                <input id="user-password-confirmation" name="password_confirmation" type="password" class="input mt-1 w-full" required autocomplete="new-password">
            </div>
        </div>
        <button class="btn-primary" type="submit">Créer l’utilisateur</button>
    </form>

    <form class="mb-4 flex gap-2">
        <label for="q" class="sr-only">Rechercher</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, email, téléphone…" class="input max-w-xs">
        <button class="btn-outline">Rechercher</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 "><tr><th>Utilisateur</th><th>Téléphone</th><th>Achats</th><th>Total dépensé</th><th>Inscrit le</th><th>Rôle</th></tr></thead>
            <tbody class="divide-y divide-slate-100 ">
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
