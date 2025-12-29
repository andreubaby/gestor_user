@props([
    'name',
    'label' => null,
    'placeholder' => null,
    'id' => null,
    'class' => '',
])

@php
    $id = $id ?: $name;
    $hasError = $errors->has($name);
@endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            {{ $attributes->merge([
                'class' => trim(
                    "w-full px-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 " .
                    ($hasError ? "border-red-300 ring-1 ring-red-200" : "border-gray-300") .
                    " {$class}"
                )
            ]) }}
        >
            @if(!is_null($placeholder))
                <option value="">{{ $placeholder }}</option>
            @endif

            {{ $slot }}
        </select>
    </div>

    @error($name)
    <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
