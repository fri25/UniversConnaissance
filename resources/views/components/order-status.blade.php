@props(['order'])
@php($classes = [
    'paid' => 'bg-emerald-100 text-emerald-800 ',
    'pending' => 'bg-amber-100 text-amber-900 ',
    'failed' => 'bg-red-100 text-red-800 ',
    'refunded' => 'bg-slate-200 text-slate-800 ',
][$order->status] ?? 'bg-slate-100 text-slate-700')
<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>{{ $order->statusLabel() }}</span>
