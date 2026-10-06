@php($editing = $author->exists)
<x-admin-layout :title="$editing ? 'Modifier un auteur' : 'Nouvel auteur'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">{{ $editing ? $author->name : 'Nouvel auteur' }}</h1>
        <a href="{{ route('admin.authors.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.authors.update', $author) : route('admin.authors.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div>
            <label for="name" class="label">Nom *</label>
            <input id="name" name="name" value="{{ old('name', $author->name) }}" required class="input">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <label for="slug" class="label">Slug</label>
            <input id="slug" name="slug" value="{{ old('slug', $author->slug) }}" class="input" placeholder="généré automatiquement">
            <x-input-error :messages="$errors->get('slug')" class="mt-1" />
        </div>
        <div>
            <label for="bio" class="label">Biographie</label>
            <textarea id="bio" name="bio" rows="6" class="input">{{ old('bio', $author->bio) }}</textarea>
        </div>
        <div>
            <label for="photo" class="label">Photo</label>
            @if ($author->photo)<img src="{{ $author->photoUrl() }}" alt="" class="mb-2 h-20 w-20 rounded-full object-cover">@endif
            <input id="photo" type="file" name="photo" accept="image/*" class="block w-full text-sm">
            <x-input-error :messages="$errors->get('photo')" class="mt-1" />
        </div>
        <button class="btn-solid">Enregistrer</button>
    </form>
</x-admin-layout>
