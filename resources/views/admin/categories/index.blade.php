<x-admin-layout title="Catégories">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Catégories</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn-solid">+ Nouvelle catégorie</a>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>Nom</th><th>Parente</th><th>E-books</th><th class="!text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                @foreach ($categories as $category)
                    <tr>
                        <td class="font-semibold text-ink dark:text-white">{{ $category->name }}</td>
                        <td>{{ $category->parent?->name ?? '—' }}</td>
                        <td>{{ $category->books_count }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('categories.show', $category) }}" class="link text-xs">Voir</a>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="link ml-3 text-xs">Modifier</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="ml-3 inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-700 hover:underline dark:text-red-300">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $categories->links() }}</div>
</x-admin-layout>
