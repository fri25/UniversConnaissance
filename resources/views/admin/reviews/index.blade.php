<x-admin-layout title="Avis">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink ">Modération des avis</h1>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'Tous', 'visible' => 'Publiés', 'hidden' => 'Masqués'] as $value => $label)
                <a href="{{ route('admin.reviews.index', array_filter(['status' => $value])) }}"
                   @class(['rounded-full px-3 py-1.5', 'bg-brand-100 font-semibold text-brand-900' => request('status', '') === $value, 'hover:bg-slate-100 ' => request('status', '') !== $value])>{{ $label }}</a>
            @endforeach
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.reviews.store') }}" class="card mb-6 space-y-4 p-5">
        @csrf
        <h2 class="text-lg font-semibold text-ink ">Ajouter un avis</h2>
        <p class="text-sm text-slate-600 ">Choisissez le compte au nom duquel l’avis sera publié.</p>

        @if ($books->isEmpty() || $users->isEmpty())
            <p class="text-sm text-amber-800 ">
                {{ $books->isEmpty() ? 'Ajoutez d’abord un livre' : 'Créez d’abord un compte utilisateur' }} pour pouvoir ajouter un avis.
            </p>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="review-user" class="label">Utilisateur</label>
                    <select id="review-user" name="user_id" class="input mt-1 w-full" required>
                        <option value="">Choisir un utilisateur</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="review-book" class="label">Livre</label>
                    <select id="review-book" name="book_id" class="input mt-1 w-full" required>
                        <option value="">Choisir un livre</option>
                        @foreach ($books as $book)
                            <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>{{ $book->title }}</option>
                        @endforeach
                    </select>
                    @error('book_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="review-rating" class="label">Note</label>
                    <select id="review-rating" name="rating" class="input mt-1 w-full" required>
                        @foreach (range(5, 1) as $rating)
                            <option value="{{ $rating }}" @selected((int) old('rating', 5) === $rating)>{{ $rating }} / 5</option>
                        @endforeach
                    </select>
                    @error('rating')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="review-comment" class="label">Commentaire (facultatif)</label>
                <textarea id="review-comment" name="comment" rows="4" maxlength="2000" class="input mt-1 w-full">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary" type="submit">Ajouter et publier l’avis</button>
        @endif
    </form>

    <div class="space-y-3">
        @forelse ($reviews as $review)
            <article class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <x-rating-stars :value="$review->rating" />
                        <span class="font-semibold text-ink ">{{ $review->user->name }}</span>
                        <span class="text-slate-500">sur</span>
                        <a href="{{ route('books.show', $review->book) }}#avis" class="link">{{ $review->book->title }}</a>
                        <span class="text-xs text-slate-500">{{ $review->created_at->translatedFormat('d/m/Y') }}</span>
                        @unless ($review->is_approved)<span class="badge bg-amber-100 text-amber-900">Masqué</span>@endunless
                    </div>
                    <p class="mt-2 text-sm text-slate-700 ">{{ $review->comment ?: '(sans commentaire)' }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <form method="POST" action="{{ route('admin.reviews.toggle', $review) }}">
                        @csrf @method('PATCH')
                        <button class="btn-outline !px-3 !py-1.5 text-xs">{{ $review->is_approved ? 'Masquer' : 'Publier' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Supprimer définitivement cet avis ?')">
                        @csrf @method('DELETE')
                        <button class="btn-danger !px-3 !py-1.5 text-xs">Supprimer</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="card p-10 text-center text-slate-500">Aucun avis.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
</x-admin-layout>
