@props([
    'label' => '',
    'value' => '0',
    'hint' => null,
    'accent' => 'slate',
])

@php
    $accentClass = match ($accent) {
        'success' => 'text-emerald-700',
        'danger' => 'text-red-700',
        'warning' => 'text-amber-700',
        'primary' => 'text-blue-700',
        default => 'text-slate-900',
    };
@endphp

<x-ui.card padding="md" :hover="false" class="h-full">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold {{ $accentClass }}">{{ $value }}</p>
    @if(!empty($hint))
        <p class="mt-1 text-[11px] text-slate-500">{{ $hint }}</p>
    @endif
</x-ui.card>

