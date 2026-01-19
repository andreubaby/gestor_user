<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fichajes diarios | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary:'#16a34a', secondary:'#15803d' },
                    boxShadow: { soft:'0 10px 25px rgba(2,6,23,.08)' }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Fichajes diarios</h1>
            </div>
        </div>

        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('usuarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Listado
            </a>

            <a href="{{ route('usuarios.vincular') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Vincular
            </a>

            <a href="{{ route('fichajes.diarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Fichajes diarios
            </a>

            <a href="{{ route('usuarios.onboarding.create') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Onboarding
            </a>

            <a href="{{ route('rrhh.documentos.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                RRHH
            </a>

            <a href="{{ route('groups.assign.create') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Asignar grupo
            </a>

            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-red-50 hover:text-red-700 transition
                      focus:outline-none focus:ring-4 focus:ring-red-200">
                Salir
            </a>
        </nav>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

<main class="max-w-8xl mx-auto space-y-5 px-4 pb-10">

    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl font-semibold text-emerald-800 tracking-tight">Fichajes del día</h2>
                <p class="text-sm text-slate-600 mt-1">
                    Día: <span class="font-semibold">{{ $date }}</span>
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('fichajes.diarios.index') }}"
              class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                              focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Mes (Excel)</label>
                <input type="month" name="month" value="{{ request('month') ?? now()->format('Y-m') }}"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                              focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Grupo</label>
                <select name="grupo"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                               focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                    <option value="">Todos</option>
                    @foreach(($groups ?? []) as $g)
                        <option value="{{ $g->id }}" {{ (string)($groupId ?? request('grupo')) === (string)$g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                <select name="estado"
                        onchange="this.form.submit()"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                               focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                    <option value="" {{ request('estado') === null || request('estado') === '' ? 'selected' : '' }}>Todos</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="md:col-span-12 flex flex-wrap gap-2">
                <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold
                               hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                    Ver
                </button>

                <a href="{{ route('fichajes.diarios.index') }}"
                   class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 hover:bg-slate-200 transition font-semibold
                          focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                    Limpiar
                </a>

                <a href="{{ route('fichajes.diarios.export', request()->query()) }}"
                   class="px-4 py-2.5 rounded-xl bg-emerald-700 text-white font-semibold
                          hover:bg-emerald-800 transition focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                    ⬇️ Excel (según filtros)
                </a>

                <span class="text-xs text-slate-500 self-center">
                    (genera 1 Excel, <span class="font-semibold">una hoja por trabajador</span>, por el mes elegido)
                </span>
            </div>
        </form>
    </section>

    {{-- Stats --}}
    <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Total</div>
            <div class="text-2xl font-semibold text-slate-900">{{ $stats['total'] ?? 0 }}</div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Ficharon</div>
            <div class="text-2xl font-semibold text-emerald-700">{{ $stats['con_fichaje'] ?? 0 }}</div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">No ficharon</div>
            <div class="text-2xl font-semibold text-red-700">{{ $stats['sin_fichaje'] ?? 0 }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Sin fichaje y sin ausencia</div>
        </div>

        {{-- ✅ NUEVO: en ausencia --}}
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">En ausencia</div>
            <div class="text-2xl font-semibold text-amber-700">{{ $stats['en_ausencia'] ?? 0 }}</div>
            <div class="mt-1 text-[11px] text-slate-500">Vacaciones / Permiso / Baja</div>
        </div>
    </section>

    {{-- Toggle rápido --}}
    <section class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" id="onlyNo" class="h-4 w-4 rounded border-slate-300">
            Mostrar solo <span class="font-semibold text-red-700">NO ficharon</span>
        </label>

        <span class="text-xs text-slate-500">
            (mantiene visibles los <span class="font-semibold">no vinculados</span>)
        </span>
    </section>

    {{-- Tabla --}}
    <section class="overflow-x-auto bg-white shadow-md rounded-2xl ring-1 ring-emerald-100">
        <table class="min-w-full divide-y divide-emerald-100 text-sm table-auto">
            <thead class="bg-gradient-to-r from-emerald-700 to-emerald-600 text-white uppercase tracking-wider text-xs sticky top-0 z-10">
            <tr>
                <th class="px-6 py-3 text-left w-[40%]">Trabajador</th>
                <th class="px-6 py-3 text-left w-[30%]">Email</th>
                <th class="px-6 py-3 text-left w-[15%]">Estado</th>
                <th class="px-6 py-3 text-left w-[15%]">Horas</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-100" id="tbodyFichajes">
            @forelse($rows as $r)
                @php
                    $ha = (($r->count ?? 0) > 0);
                    $entrada = $r->first_in ?? null;
                    $salida  = $r->last_out ?? null;

                    $absence = $r->absence_tipo ?? null; // 'V'|'P'|'B'|null
                    $absenceLabel = match($absence) {
                        'V' => '🏖 Vacaciones',
                        'P' => '📝 Permiso',
                        'B' => '🏥 Baja',
                        default => null,
                    };

                    // badges (estado)
                    $estadoBadge = 'bg-slate-100 text-slate-600';
                    $estadoText  = '— Sin datos';
                    $rowTone     = 'bg-slate-50';

                    if ($r->vinculado_fichajes) {
                        if ($ha) {
                            $estadoBadge = 'bg-emerald-100 text-emerald-800';
                            $estadoText  = '✅ Fichó';
                            $rowTone     = 'bg-emerald-50/60';
                        } else {
                            // ✅ si no fichó pero está en ausencia -> mostrar causa
                            if ($absenceLabel) {
                                $estadoBadge = 'bg-amber-100 text-amber-800';
                                $estadoText  = $absenceLabel;
                                $rowTone     = 'bg-amber-50/60';
                            } else {
                                $estadoBadge = 'bg-red-100 text-red-700';
                                $estadoText  = '❌ No fichó';
                                $rowTone     = 'bg-red-50/50';
                            }
                        }
                    } else {
                        $estadoBadge = 'bg-slate-100 text-slate-600';
                        $estadoText  = '⚠ No vinculado';
                        $rowTone     = 'bg-slate-50';
                    }

                    // chips horas
                    $chipInClass  = $entrada ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200';
                    $chipOutClass = $salida  ? 'bg-red-100 text-red-700 ring-1 ring-red-200'           : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200';

                    $exportParams = array_merge(request()->query(), [
                        'trabajador_id' => $r->trabajador_id ?? $r->id ?? null,
                    ]);

                    // ✅ para el toggle: "no fichó real" = vinculado + !ha + !absence
                    $isNoReal = ($r->vinculado_fichajes && !$ha && !$absenceLabel);

                   // ✅ Override para INACTIVOS: mostrar "--" en gris en vez de "No fichó"
                    if (isset($r->activo) && (int)$r->activo === 0) {
                        // Si quieres que SIEMPRE ponga "--" aunque tuviera otros estados:
                        $estadoBadge = 'bg-slate-100 text-slate-400';
                        $estadoText  = '--';
                        $rowTone     = 'bg-slate-50';

                        // (Opcional) también poner chips en gris si quieres:
                        // $chipInClass  = 'bg-slate-100 text-slate-400 ring-1 ring-slate-200';
                        // $chipOutClass = 'bg-slate-100 text-slate-400 ring-1 ring-slate-200';
                    }
                @endphp

                <tr class="transition hover:bg-emerald-50/40 {{ $rowTone }}"
                    data-has="{{ $ha ? 1 : 0 }}"
                    data-vinc="{{ $r->vinculado_fichajes ? 1 : 0 }}"
                    data-noreal="{{ $isNoReal ? 1 : 0 }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-slate-900">{{ $r->nombre }}</span>

                            @if(!empty($exportParams['trabajador_id']))
                                <a href="{{ route('fichajes.diarios.export', $exportParams) }}"
                                   class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-semibold
                                          bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                   title="Excel mensual de este trabajador">
                                    📄 Excel
                                </a>
                            @endif

                            @if(isset($r->activo))
                                @if((int)$r->activo === 1)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activo</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">Inactivo</span>
                                @endif
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4 text-slate-600">
                        <span class="break-all">{{ $r->email }}</span>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs rounded-full font-semibold {{ $estadoBadge }}">
                            {{ $estadoText }}
                        </span>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold {{ $chipInClass }}" title="Entrada">
                                ⏱ <span>In</span> <span class="tabular-nums">{{ $entrada ?? '—' }}</span>
                            </span>

                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold {{ $chipOutClass }}" title="Salida">
                                ⏱ <span>Out</span> <span class="tabular-nums">{{ $salida ?? '—' }}</span>
                            </span>
                        </div>

                        @if(!$entrada && !$salida && ($r->last_any ?? null))
                            <div class="mt-1 text-[11px] text-slate-500">
                                Último: <span class="font-semibold tabular-nums">{{ $r->last_any }}</span>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-slate-500">
                        No hay trabajadores para ese filtro.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

</main>

<script>
    // Mostrar solo NO ficharon (pero dejando "no vinculado" visible también)
    // ✅ Ahora "NO ficharon" = vinculado + sin fichaje + sin ausencia (no cuenta vacaciones/permiso/baja)
    document.addEventListener('DOMContentLoaded', () => {
        const cb = document.getElementById('onlyNo');
        const tbody = document.getElementById('tbodyFichajes');
        if (!cb || !tbody) return;

        const apply = () => {
            const onlyNo = cb.checked;

            [...tbody.querySelectorAll('tr')].forEach(tr => {
                const vinc = tr.dataset.vinc === '1';
                const noReal = tr.dataset.noreal === '1';

                // onlyNo: mostrar "no real" y "no vinculado"
                // ocultar: ficharon y ausencias
                if (onlyNo) {
                    if (!vinc) {
                        tr.classList.remove('hidden'); // no vinculado siempre visible
                        return;
                    }
                    if (noReal) tr.classList.remove('hidden');
                    else tr.classList.add('hidden');
                    return;
                }

                tr.classList.remove('hidden');
            });
        };

        cb.addEventListener('change', apply);
        apply();
    });
</script>

</body>
</html>
