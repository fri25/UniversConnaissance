@props(['book', 'owned' => false])
@php($owned = $owned || in_array($book->id, auth()->user()?->ownedBookIds() ?? [], true))
<article {{ $attributes->merge(['class' => 'group flex flex-col']) }} data-reveal>
    <a href="{{ route('books.show', $book) }}" class="relative block overflow-hidden rounded-xl bg-slate-100 shadow-cover transition duration-300 group-hover:-translate-y-1.5 group-hover:shadow-xl dark:bg-ink-700">
        <img src="{{ $book->coverUrl() }}" alt="Couverture de {{ $book->title }}" loading="lazy" width="400" height="400"
             class="aspect-cover w-full object-cover transition duration-500 group-hover:scale-[1.04]">

        <span class="absolute left-2 top-2 flex flex-col items-start gap-1">
            @if ($book->isOnPromo())
                <span class="badge bg-rose-600 text-white shadow">Promo −{{ $book->discountPercent() }} %</span>
            @elseif ($book->isNew())
                <span class="badge bg-brand-500 text-ink shadow">Nouveau</span>
            @endif
        </span>
        <span class="absolute bottom-2 right-2 flex gap-1">
            @foreach ($book->availableFormats() as $fmt)
                <span class="badge bg-ink/80 text-[0.65rem] uppercase tracking-wide text-white backdrop-blur">{{ $fmt }}</span>
            @endforeach
        </span>
    </a>

    <div class="mt-3 flex flex-1 flex-col">
        @if ($book->categories->isNotEmpty())
            <a href="{{ route('categories.show', $book->categories->first()) }}" class="text-xs font-semibold uppercase tracking-wide text-brand-700 hover:underline dark:text-brand-300">
                {{ $book->categories->first()->name }}
            </a>
        @endif
        <h3 class="mt-1 font-serif text-base font-bold leading-snug text-ink dark:text-white">
            <a href="{{ route('books.show', $book) }}" class="hover:text-brand-700 dark:hover:text-brand-300">{{ $book->title }}</a>
        </h3>
        <p class="mt-0.5 line-clamp-1 text-sm text-slate-600 dark:text-slate-400">{{ $book->authorNames() }}</p>
        <x-rating-stars class="mt-1.5" :value="$book->rating_avg" :count="$book->rating_count ?? 0" />
        <x-book-price :book="$book" class="mt-2" />

        <div class="mt-auto grid grid-cols-2 gap-2 pt-3">
            <a href="{{ route('books.show', $book) }}" class="btn-outline !px-3 !py-2 text-xs">Voir détails</a>
            @if ($owned)
                <a href="{{ route('dashboard') }}" class="btn-solid !px-3 !py-2 text-xs">Télécharger</a>
            @elseif ($book->isPurchasable())
                <a href="{{ route('books.buy', $book) }}" data-pixel-checkout="{{ json_encode(\App\Services\MetaPixel::bookData($book)) }}" class="btn-primary !px-3 !py-2 text-xs">Acheter</a>
            @else
                <span class="btn-ghost !px-3 !py-2 text-xs opacity-70" aria-disabled="true">Bientôt</span>
            @endif
        </div>
    </div>
</article>
