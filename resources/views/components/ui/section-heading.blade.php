@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->class('mb-4 flex flex-wrap items-start justify-between gap-3') }}>
    <div>
        <h2 class="ui-title text-xl font-semibold text-white" style="color: #f8fafc;">{{ $title }}</h2>
        @if(!empty($subtitle))
            <p class="mt-1 text-sm text-slate-200" style="color: #e2e8f0;">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>

