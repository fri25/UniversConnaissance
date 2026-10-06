@props(['compact' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="h-9 w-9 shrink-0" viewBox="0 0 40 40" aria-hidden="true">
        <defs>
            <linearGradient id="uc-logo-g" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#72D7F8"/>
                <stop offset="1" stop-color="#10BAF1"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#uc-logo-g)"/>
        <ellipse cx="20" cy="20" rx="15" ry="6" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="1.3" transform="rotate(-20 20 20)"/>
        <path d="M20 13.5c-2.6-1.6-5.6-2-8.5-1.6v14.4c2.9-.4 5.9 0 8.5 1.6 2.6-1.6 5.6-2 8.5-1.6V11.9c-2.9-.4-5.9 0-8.5 1.6Z" fill="#fff"/>
        <path d="M20 13.5v14.4" stroke="#10BAF1" stroke-width="1.2"/>
        <circle cx="31" cy="11" r="2" fill="#fff"/>
    </svg>
    @unless ($compact)
        <span class="leading-none">
            <span class="block font-serif text-lg font-bold tracking-tight text-ink dark:text-white">Univers</span>
            <span class="block text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">Connaissance</span>
        </span>
    @endunless
</span>
