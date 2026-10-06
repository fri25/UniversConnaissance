@props(['order'])
@php($classes = [
    'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
    'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100',
    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
    'refunded' => 'bg-slate-200 text-slate-800 dark:bg-ink-600 dark:text-slate-200',
][$order->status] ?? 'bg-slate-100 text-slate-700')
<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>{{ $order->statusLabel() }}</span>
