@props(['book', 'large' => false])
<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-baseline gap-x-2']) }}>
    <span class="{{ $large ? 'text-3xl' : 'text-lg' }} font-bold text-ink dark:text-white">{{ fcfa($book->price) }}</span>
    @if ($book->isOnPromo())
        <span class="{{ $large ? 'text-lg' : 'text-sm' }} text-slate-500 line-through dark:text-slate-400">
            <span class="sr-only">Ancien prix :</span>{{ fcfa($book->old_price) }}
        </span>
        @if ($large)
            <span class="badge bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-100">−{{ $book->discountPercent() }} %</span>
        @endif
    @endif
</span>
