<x-admin-layout title="Auteurs">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Auteurs</h1>
        <a href="{{ route('admin.authors.create') }}" class="btn-solid">+ Nouvel auteur</a>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>Nom</th><th>E-books</th><th class="!text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                @foreach ($authors as $author)
                    <tr>
                        <td class="font-semibold text-ink dark:text-white">{{ $author->name }}</td>
                        <td>{{ $author->books_count }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.authors.edit', $author) }}" class="link text-xs">Modifier</a>
                            <form method="POST" action="{{ route('admin.authors.destroy', $author) }}" class="ml-3 inline" onsubmit="return confirm('Supprimer cet auteur ?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-700 hover:underline dark:text-red-300">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $authors->links() }}</div>
</x-admin-layout>
