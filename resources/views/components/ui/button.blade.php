@props([
    'as' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-4';

    $variantClass = match ($variant) {
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus:ring-slate-200',
        'ghost' => 'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 focus:ring-slate-200',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-200',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-200',
        default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-200',
    };

    $sizeClass = match ($size) {
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-5 py-3 text-sm',
        default => 'px-4 py-2 text-sm',
    };

    $classes = trim("{$base} {$variantClass} {$sizeClass}");
@endphp

@if($as === 'a')
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif

