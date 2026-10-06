@props(['value' => null, 'count' => null, 'size' => 'h-4 w-4'])
@php($rounded = $value ? round($value * 2) / 2 : 0)
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <span class="flex" role="img" aria-label="{{ $value ? 'Note : '.number_format($value, 1, ',', '').' sur 5' : 'Pas encore noté' }}">
        @for ($i = 1; $i <= 5; $i++)
            <svg class="{{ $size }} {{ $rounded >= $i ? 'text-amber-400' : ($rounded >= $i - 0.5 ? 'text-amber-300' : 'text-slate-300 dark:text-ink-600') }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.78.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29Z"/>
            </svg>
        @endfor
    </span>
    @if ($count !== null)
        <span class="text-xs text-slate-500 dark:text-slate-400">
            @if ($value) {{ number_format($value, 1, ',', '') }} @endif ({{ $count }})
        </span>
    @endif
</span>
