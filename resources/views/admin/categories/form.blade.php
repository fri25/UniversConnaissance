@php($editing = $category->exists)
<x-admin-layout :title="$editing ? 'Modifier une catégorie' : 'Nouvelle catégorie'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">{{ $editing ? $category->name : 'Nouvelle catégorie' }}</h1>
        <a href="{{ route('admin.categories.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div>
            <label for="name" class="label">Nom *</label>
            <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="input">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <label for="slug" class="label">Slug</label>
            <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="input" placeholder="généré automatiquement">
            <x-input-error :messages="$errors->get('slug')" class="mt-1" />
        </div>
        <div>
            <label for="parent_id" class="label">Catégorie parente</label>
            <select id="parent_id" name="parent_id" class="input">
                <option value="">— Aucune —</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
        </div>
        <div>
            <label for="description" class="label">Description</label>
            <textarea id="description" name="description" rows="4" class="input">{{ old('description', $category->description) }}</textarea>
        </div>
        <button class="btn-solid">Enregistrer</button>
    </form>
</x-admin-layout>
