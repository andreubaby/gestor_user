@php
    $messages = [
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
    ];

    $styles = [
        'success' => [
            'container' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700',
            'title' => 'Listo',
            'icon' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
        ],
        'error' => [
            'container' => 'border-red-500/30 bg-red-500/10 text-red-700',
            'title' => 'Error',
            'icon' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
        ],
        'warning' => [
            'container' => 'border-amber-500/30 bg-amber-500/10 text-amber-700',
            'title' => 'Atencion',
            'icon' => 'M8.257 3.099c.765-1.36 2.722-1.36 3.487 0l6.518 11.591c.75 1.334-.213 2.99-1.742 2.99H3.48c-1.53 0-2.492-1.656-1.743-2.99L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v4a1 1 0 102 0V7a1 1 0 00-1-1z',
        ],
    ];
@endphp

@foreach($messages as $type => $message)
    @if(!empty($message))
        @php($style = $styles[$type])
        <div class="mb-4 flex items-start gap-3 rounded-lg border p-4 text-sm font-medium {{ $style['container'] }}">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="{{ $style['icon'] }}" clip-rule="evenodd"></path>
            </svg>
            <div>
                <p class="font-semibold">{{ $style['title'] }}</p>
                <p>{{ $message }}</p>
            </div>
        </div>
    @endif
@endforeach

