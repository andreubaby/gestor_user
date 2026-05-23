@extends('layouts.unified-automation')

@section('title', 'Vista previa no fichados | Automatizacion WhatsApp')

@section('content')
<div class="text-slate-900">
    <x-ui.section-heading title="Vista previa: recordatorio de no fichaje" subtitle="Aqui puedes ver a quien se le enviaria el mensaje automatico para la fecha seleccionada.">
        <x-slot:actions>
            <x-ui.button as="a" :href="route('automation.sequences.index')" variant="secondary">Secuencias</x-ui.button>
            <x-ui.button as="a" :href="route('openwa.auto.index')" variant="ghost">OpenWA Automatico</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    <x-ui.card class="mb-4" :hover="false">
        <form method="GET" action="{{ route('automation.missing-punch.preview') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date" class="block text-xs font-semibold uppercase text-slate-600 mb-1">Fecha a revisar</label>
                <input id="date" name="date" type="date" value="{{ $selectedDate }}" class="px-3 py-2 rounded-lg border border-slate-300" />
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Actualizar vista previa</button>
        </form>

        @if(isset($errors) && $errors->any())
            <div class="mt-3 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
    </x-ui.card>

    <section class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
        <x-ui.stat-card label="Fecha" :value="$report['date']" />
        <x-ui.stat-card label="Trabajadores activos evaluados" :value="$report['total_linked'] ?? 0" />
        <x-ui.stat-card label="Con fichaje" :value="$report['total_with_punch'] ?? 0" />
        <x-ui.stat-card label="En ausencia" :value="$report['total_absent'] ?? 0" accent="warning" />
        <x-ui.stat-card label="Se enviaria mensaje" :value="$report['total_candidates'] ?? 0" accent="success" />
    </section>

    <x-ui.card class="mb-4" :hover="false">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h2 class="text-lg font-bold">Estado de evaluacion</h2>
            @if(($report['status'] ?? 'ok') === 'ok')
                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">Operativo</span>
            @else
                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">Sin envios</span>
            @endif
        </div>

        @if(!empty($report['reason']))
            <p class="mt-2 text-sm text-slate-600">
                Motivo: <span class="font-semibold">{{ $report['reason'] }}</span>
            </p>
        @endif

        <p class="mt-3 text-sm text-slate-600">
            Plantilla de mensaje:
            <span class="font-medium">{{ $messageTemplate }}</span>
        </p>

        @if(($report['total_omitted'] ?? 0) > 0)
            <p class="mt-3 text-sm text-rose-700">
                Excluidos por email omitido: <span class="font-semibold">{{ $report['total_omitted'] }}</span>
            </p>
        @endif
    </x-ui.card>

    <x-ui.card class="overflow-hidden" :hover="false" padding="none">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-bold">Destinatarios previstos</h2>
            <span class="text-xs font-semibold text-slate-500">{{ count($report['candidates'] ?? []) }} resultado(s)</span>
        </div>

        @if(!empty($report['candidates']))
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" data-enhanced-table>
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold">Trabajador ID</th>
                        <th class="text-left px-4 py-2 font-semibold">Nombre</th>
                        <th class="text-left px-4 py-2 font-semibold">Email</th>
                        <th class="text-left px-4 py-2 font-semibold">Telefono</th>
                        <th class="text-left px-4 py-2 font-semibold">Usuario ID</th>
                        <th class="text-left px-4 py-2 font-semibold">User fichaje ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($report['candidates'] as $candidate)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2">{{ $candidate['trabajador_id'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $candidate['nombre'] ?: '-' }}</td>
                            <td class="px-4 py-2">{{ $candidate['email'] ?: '-' }}</td>
                            <td class="px-4 py-2">{{ $candidate['tfno'] ?: '-' }}</td>
                            <td class="px-4 py-2">{{ $candidate['usuario_id'] ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $candidate['user_fichaje_id'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-sm text-slate-600">
                No hay destinatarios para la fecha seleccionada.
            </div>
        @endif
    </x-ui.card>
    </div>
@endsection






