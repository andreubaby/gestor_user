@extends('layouts.unified-automation')

@section('title', 'Programar: ' . $sequence->name)

@push('head')
    <style>
        * {
            --font-title: 'Poppins', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0c4a6e 100%);
            min-height: 100vh;
        }

        .title-font { font-family: var(--font-title); letter-spacing: -0.5px; }
        .gradient-primary { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); }
        .gradient-card { background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }

        @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slide-in { animation: slideInUp 0.6s ease-out forwards; }

        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15); }

        .btn-hover { transition: all 0.2s ease; }
        .btn-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }

        .input-focus:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        .day-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 8px; }
    </style>
@endpush

@section('content')
<div class="text-slate-900">

<div class="min-h-screen">
    <main class="max-w-3xl mx-auto px-6 py-12">
        <x-ui.section-heading title="Nueva Programación" :subtitle="'para: ' . $sequence->name" />

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-700 text-sm font-medium animate-slide-in">
                <p class="font-semibold mb-2">⚠️ Errores:</p>
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('automation.sequences.storeSchedule', $sequence) }}" class="space-y-6">
            @csrf

            <!-- Hora -->
            <section class="gradient-card rounded-2xl p-8 card-hover animate-slide-in">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00-.293.707l-2.828 2.829a1 1 0 101.415 1.415L9 9.586V6z"/></svg>
                    </div>
                    <h2 class="title-font text-xl font-bold">Hora de Ejecución</h2>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">¿A qué hora? *</label>
                    <input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}" required class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white input-focus outline-none text-lg">
                    <p class="text-xs text-slate-600 mt-2">⏰ La secuencia se ejecutará diariamente a esta hora en los días seleccionados</p>
                </div>
            </section>

            <!-- Días -->
            <section class="gradient-card rounded-2xl p-8 card-hover animate-slide-in" style="animation-delay: 0.1s">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 000 2h8a1 1 0 100-2H6zM4 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/></svg>
                    </div>
                    <h2 class="title-font text-xl font-bold">Días de la Semana</h2>
                </div>

                <div class="day-grid">
                    @php
                        $days = [
                            '1' => ['label' => 'Lunes', 'emoji' => '📅'],
                            '2' => ['label' => 'Martes', 'emoji' => '📅'],
                            '3' => ['label' => 'Miércoles', 'emoji' => '📅'],
                            '4' => ['label' => 'Jueves', 'emoji' => '📅'],
                            '5' => ['label' => 'Viernes', 'emoji' => '📅'],
                            '6' => ['label' => 'Sábado', 'emoji' => '🏖️'],
                            '0' => ['label' => 'Domingo', 'emoji' => '☀️'],
                        ];
                        $selected = old('days_of_week', ['2', '3', '4', '5']);
                        $blockedDays = ['0', '1', '6'];
                    @endphp

                    @foreach($days as $value => $day)
                        @php($isBlocked = in_array($value, $blockedDays, true))
                        <label class="relative cursor-pointer group">
                            <input type="checkbox" name="days_of_week[]" value="{{ $value }}" {{ !$isBlocked && in_array($value, is_array($selected) ? $selected : []) ? 'checked' : '' }} {{ $isBlocked ? 'disabled' : '' }} class="sr-only peer">
                            <div class="px-3 py-3 rounded-lg border-2 text-center font-semibold text-sm transition-all {{ $isBlocked ? 'border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed' : 'border-slate-200 bg-white peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:border-blue-300' }}">
                                <div class="text-lg">{{ $day['emoji'] }}</div>
                                <div class="text-xs mt-1">{{ substr($day['label'], 0, 3) }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-600 mt-4">✅ Selecciona los dias en los que se debe ejecutar la secuencia (solo martes a viernes)</p>
            </section>

            <!-- Resumen -->
            <section class="gradient-card rounded-2xl p-8 card-hover animate-slide-in" style="animation-delay: 0.2s">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2H6a2 2 0 00-2 2v1a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v1a2 2 0 002 2h8a2 2 0 002-2v-1a1 1 0 000-2v-3a1 1 0 000-2V7a1 1 0 000-2v-1a2 2 0 00-2-2h1a1 1 0 100 2h2a2 2 0 012 2v1a1 1 0 000 2v3a1 1 0 000 2v3a1 1 0 000 2v1a2 2 0 01-2 2H6a2 2 0 01-2-2v-1a1 1 0 000-2v-3a1 1 0 000-2V7a1 1 0 000-2v-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2 class="title-font text-xl font-bold">Resumen</h2>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center p-3 rounded-lg bg-slate-50">
                        <span class="text-slate-600">📝 Secuencia:</span>
                        <span class="font-semibold text-slate-900">{{ $sequence->name }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-lg bg-slate-50">
                        <span class="text-slate-600">⚡ Pasos:</span>
                        <span class="font-semibold text-slate-900">{{ count($sequence->actions) }} acciones</span>
                    </div>
                    <div class="flex justify-between items-center p-3 rounded-lg bg-blue-50">
                        <span class="text-slate-600">🕐 Ejecutará a:</span>
                        <input type="time" id="preview-time" class="font-semibold text-blue-900 bg-transparent border-none outline-none w-24 text-right" readonly>
                    </div>
                </div>
            </section>

            <!-- Botones -->
            <div class="flex gap-4 animate-slide-in" style="animation-delay: 0.3s">
                <a href="{{ route('automation.sequences.show', $sequence) }}" class="flex-1 px-6 py-3 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-center btn-hover hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-6 py-3 gradient-primary rounded-lg text-white font-semibold btn-hover shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1V3a1 1 0 011-1h1V1a1 1 0 011 1v1h1V3a1 1 0 011-1h1V1a1 1 0 011 1v1h1V3a1 1 0 011-1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v1h1a1 1 0 110 2h-1v1h1a1 1 0 110 2h-1v1h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2a1 1 0 01-1-1v-1h-1v1a1 1 0 01-1 1h-1v1a1 1 0 01-1-1v-1h-1v1a1 1 0 01-1 1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1v-1H2a1 1 0 110-2h1v-1H2a1 1 0 110-2h1V9H2a1 1 0 110-2h1V6H2a1 1 0 110-2h1V4a2 2 0 012-2z" clip-rule="evenodd"/></svg>
                    Programar Secuencia
                </button>
            </div>
        </form>
    </main>
    </div>
@endsection

@section('page_scripts')
<script>
document.querySelector('input[type="time"]').addEventListener('change', function() {
    document.getElementById('preview-time').value = this.value;
});

// Set initial preview
document.getElementById('preview-time').value = document.querySelector('input[type="time"]').value || '';
</script>
@endsection

