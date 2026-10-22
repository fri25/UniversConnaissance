@props(['book', 'large' => false])
<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-baseline gap-x-2']) }}>
    <span class="{{ $large ? 'text-3xl' : 'text-lg' }} font-bold text-ink ">{{ fcfa($book->price) }}</span>
    @if ($book->isOnPromo())
        <span class="{{ $large ? 'text-lg' : 'text-sm' }} text-slate-500 line-through ">
            <span class="sr-only">Ancien prix :</span>{{ fcfa($book->old_price) }}
        </span>
        @if ($large)
            <span class="badge bg-rose-100 text-rose-800 ">−{{ $book->discountPercent() }} %</span>
        @endif
    @endif
</span>
