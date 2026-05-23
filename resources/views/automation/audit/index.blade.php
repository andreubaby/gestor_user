@extends('layouts.unified-automation')

@section('title', 'Auditoria de Secuencias')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-2 space-y-4 text-slate-900">
    <x-ui.section-heading title="Auditoria central de automatizaciones" subtitle="Trazabilidad de ejecuciones, errores y duplicados por secuencia.">
        <x-slot:actions>
            <x-ui.button as="a" :href="route('automation.sequences.index')" variant="secondary">Volver a secuencias</x-ui.button>
            <x-ui.button as="a" :href="route('automation.audit.exportCsv', request()->query())">Exportar CSV</x-ui.button>
        </x-slot:actions>
    </x-ui.section-heading>

    <x-ui.card :hover="false">
    <form method="GET" action="{{ route('automation.audit.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
        <div>
            <label for="filter-from" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Desde</label>
            <input id="filter-from" type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded border border-slate-300 px-2 py-2 text-sm">
        </div>
        <div>
            <label for="filter-to" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Hasta</label>
            <input id="filter-to" type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded border border-slate-300 px-2 py-2 text-sm">
        </div>
        <div>
            <label for="filter-status" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estado</label>
            <select id="filter-status" name="status" class="w-full rounded border border-slate-300 px-2 py-2 text-sm">
                <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="queued" {{ $filters['status'] === 'queued' ? 'selected' : '' }}>queued</option>
                <option value="executed" {{ $filters['status'] === 'executed' ? 'selected' : '' }}>executed</option>
                <option value="failed" {{ $filters['status'] === 'failed' ? 'selected' : '' }}>failed</option>
                <option value="duplicate_blocked" {{ $filters['status'] === 'duplicate_blocked' ? 'selected' : '' }}>duplicate_blocked</option>
            </select>
        </div>
        <div>
            <label for="filter-sequence" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Secuencia</label>
            <select id="filter-sequence" name="sequence_id" class="w-full rounded border border-slate-300 px-2 py-2 text-sm">
                <option value="all">Todas</option>
                @foreach($sequences as $sequence)
                    <option value="{{ $sequence->id }}" {{ $filters['sequence_id'] == (string) $sequence->id ? 'selected' : '' }}>{{ $sequence->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="filter-destination" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Destino contiene</label>
            <input id="filter-destination" type="text" name="destination" value="{{ $filters['destination'] }}" placeholder="telefono, grupo, chat_id..." class="w-full rounded border border-slate-300 px-2 py-2 text-sm">
        </div>
        <div class="md:col-span-6 flex gap-2">
            <x-ui.button type="submit">Aplicar filtros</x-ui.button>
            <x-ui.button as="a" :href="route('automation.audit.index')" variant="ghost">Limpiar</x-ui.button>
        </div>
    </form>
    </x-ui.card>

    <x-ui.card class="overflow-x-auto" :hover="false" padding="none">
        <table class="min-w-full text-sm" data-enhanced-table>
            <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left px-3 py-2">Fecha</th>
                <th class="text-left px-3 py-2">Secuencia</th>
                <th class="text-left px-3 py-2">Paso</th>
                <th class="text-left px-3 py-2">Estado</th>
                <th class="text-left px-3 py-2">Destino</th>
                <th class="text-left px-3 py-2">Mensaje</th>
            </tr>
            </thead>
            <tbody>
            @forelse($events as $event)
                <tr class="border-t border-slate-100">
                    <td class="px-3 py-2 whitespace-nowrap">{{ $event->happened_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="px-3 py-2">{{ $event->automationSequence?->name ?? 'N/D' }}</td>
                    <td class="px-3 py-2">{{ $event->step_number ?? '—' }}</td>
                    <td class="px-3 py-2 font-semibold">{{ $event->status }}</td>
                    <td class="px-3 py-2">{{ $event->target_label ?: '—' }}</td>
                    <td class="px-3 py-2 max-w-[420px] truncate" title="{{ $event->message }}">{{ $event->message ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-slate-500">Sin eventos con los filtros actuales.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </x-ui.card>

    <div>{{ $events->links() }}</div>
    </div>
@endsection


