@props([
    'padding' => 'md',
    'hover' => true,
    'tone' => 'default',
])

@php
    $paddingClass = match ($padding) {
        'none' => '',
        'sm' => 'p-3',
        'lg' => 'p-6',
        default => 'p-4',
    };

    $toneClass = match ($tone) {
        'soft' => 'bg-slate-50 border-slate-200',
        'glass' => 'bg-white/80 border-white/30 backdrop-blur',
        default => 'bg-white border-slate-200',
    };

    $hoverClass = $hover ? 'transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg' : '';
@endphp

<div {{ $attributes->class("rounded-2xl border shadow-sm {$toneClass} {$paddingClass} {$hoverClass}") }}>
    {{ $slot }}
</div>

