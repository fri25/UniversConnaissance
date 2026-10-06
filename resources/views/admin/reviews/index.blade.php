<x-admin-layout title="Avis">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Modération des avis</h1>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'Tous', 'visible' => 'Publiés', 'hidden' => 'Masqués'] as $value => $label)
                <a href="{{ route('admin.reviews.index', array_filter(['status' => $value])) }}"
                   @class(['rounded-full px-3 py-1.5', 'bg-brand-100 font-semibold text-brand-900' => request('status', '') === $value, 'hover:bg-slate-100 dark:hover:bg-ink-700' => request('status', '') !== $value])>{{ $label }}</a>
            @endforeach
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse ($reviews as $review)
            <article class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <x-rating-stars :value="$review->rating" />
                        <span class="font-semibold text-ink dark:text-white">{{ $review->user->name }}</span>
                        <span class="text-slate-500">sur</span>
                        <a href="{{ route('books.show', $review->book) }}#avis" class="link">{{ $review->book->title }}</a>
                        <span class="text-xs text-slate-500">{{ $review->created_at->translatedFormat('d/m/Y') }}</span>
                        @unless ($review->is_approved)<span class="badge bg-amber-100 text-amber-900">Masqué</span>@endunless
                    </div>
                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $review->comment ?: '(sans commentaire)' }}</p>
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
