<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FichajesDiariosService
{
    public function handle(Request $request): array
    {
        $date    = $request->input('date') ?: now()->format('Y-m-d'); // YYYY-mm-dd
        $groupId = $request->input('grupo'); // opcional

        // ✅ estado '' | 'activo' | 'inactivo'
        $estado = (string)($request->input('estado', '') ?? '');
        if (!in_array($estado, ['', 'activo', 'inactivo'], true)) {
            $estado = '';
        }

        /**
         * =========================
         * 1) TRABAJADORES (polifonía) + filtros estado/grupo
         * =========================
         */
        $trabajadoresQuery = TrabajadorPolifonia::query()
            ->select(['id', 'nombre', 'email', 'activo'])
            ->orderBy('nombre');

        if ($estado === 'activo') {
            $trabajadoresQuery->where('activo', 1);
        } elseif ($estado === 'inactivo') {
            $trabajadoresQuery->where('activo', 0);
        }

        // Si hay grupo, filtramos por pivot (BD principal mysql)
        if ($groupId !== null && $groupId !== '') {
            $ids = DB::connection('mysql')
                ->table('group_trabajador')
                ->where('group_id', (int)$groupId)
                ->pluck('trabajador_id')
                ->map(fn ($v) => (int)$v)
                ->all();

            if (empty($ids)) {
                return [
                    'date'    => $date,
                    'groupId' => $groupId,
                    'estado'  => $estado,
                    'rows'    => collect(),
                    'stats'   => [
                        'total'        => 0,
                        'con_fichaje'  => 0,
                        'sin_fichaje'  => 0,
                        'en_ausencia'  => 0,
                        'solo_entrada' => 0,
                        'solo_salida'  => 0,
                    ],
                ];
            }

            $trabajadoresQuery->whereIn('id', $ids);
        }

        $trabajadores = $trabajadoresQuery->get();

        /**
         * =========================
         * 1.5) AUSENCIAS DEL DÍA (V/P/B) por trabajador
         * =========================
         * ✅ Se calcula por FECHA (no por vacation_year) => evita bugs de años cruzados.
         * Prioridad si hubiera más de una: B > P > V
         */
        $trabajadorIds = $trabajadores->pluck('id')->map(fn ($v) => (int)$v)->values();

        $absenceMap = collect(); // trabajador_id => 'V'|'P'|'B'
        if ($trabajadorIds->isNotEmpty()) {
            $absRaw = DB::connection('mysql_polifonia') // ⚠️ cambia a tu conexión real si no es mysql
            ->table('trabajadores_dias')    // ⚠️ cambia si la tabla se llama distinto
            ->select(['trabajador_id', 'tipo'])
                ->whereIn('trabajador_id', $trabajadorIds->all())
                ->whereDate('fecha', $date)
                ->get();

            $prio = fn (string $tipo) => match (strtoupper($tipo)) {
                'B' => 3,
                'P' => 2,
                'V' => 1,
                default => 0,
            };

            $absenceMap = collect($absRaw)
                ->groupBy(fn ($r) => (int)$r->trabajador_id)
                ->map(function ($items) use ($prio) {
                    $best = null;
                    $bestPrio = 0;

                    foreach ($items as $it) {
                        $t = strtoupper((string)($it->tipo ?? ''));
                        $p = $prio($t);
                        if ($p > $bestPrio) {
                            $bestPrio = $p;
                            $best = $t;
                        }
                    }

                    return $best; // 'B'|'P'|'V'|null
                });
        }

        /**
         * =========================
         * 2) VÍNCULOS trabajador_id -> user_fichaje_id (BD principal)
         * =========================
         */
        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadores->pluck('id'))
            ->get(['trabajador_id', 'user_fichaje_id']);

        $mapTrabajadorToFichajes = $vinculos
            ->filter(fn ($v) => !empty($v->user_fichaje_id))
            ->mapWithKeys(fn ($v) => [(int)$v->trabajador_id => (int)$v->user_fichaje_id]);

        $fichajesUserIds = $mapTrabajadorToFichajes->values()->unique()->values();

        /**
         * =========================
         * 3) RANGO DEL DÍA
         * =========================
         */
        $start = Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');

        /**
         * =========================
         * A) FICHAJES NUEVOS (mysql_fichajes.punches)
         * =========================
         */
        $punches = collect();

        if ($fichajesUserIds->isNotEmpty()) {
            $punches = DB::connection('mysql_fichajes')
                ->table('punches')
                ->select(['id', 'user_id', 'type', 'mood', 'happened_at', 'is_manual', 'note'])
                ->whereIn('user_id', $fichajesUserIds->all())
                ->whereBetween('happened_at', [$start, $end])
                ->orderBy('happened_at')
                ->get();
        }

        // Normalizar punches nuevos (por trabajador_id)
        $punchesFichajes = $punches
            ->map(function ($p) {
                $type = strtolower((string)($p->type ?? ''));

                return [
                    'origen'        => 'fichajes',
                    'trabajador_id' => null, // se asigna luego
                    'type'          => $type === 'in' ? 'in' : ($type === 'out' ? 'out' : $type),
                    'hora'          => $p->happened_at ? Carbon::parse($p->happened_at)->format('H:i') : '—',
                    'datetime'      => $p->happened_at,
                    'mood'          => is_null($p->mood) ? null : (int)$p->mood,
                    'is_manual'     => (int)($p->is_manual ?? 0),
                    'note'          => $p->note ?? null,
                    'raw_user_id'   => (int)$p->user_id,
                ];
            })
            ->values();

        // user_id fichajes -> trabajador_id
        $mapFichajesToTrabajador = $mapTrabajadorToFichajes->flip(); // [user_fichaje_id => trabajador_id]

        $punchesFichajes = $punchesFichajes
            ->map(function ($p) use ($mapFichajesToTrabajador) {
                $tid = $mapFichajesToTrabajador->get($p['raw_user_id']);
                if ($tid) {
                    $p['trabajador_id'] = (int)$tid;
                }
                return $p;
            })
            ->filter(fn ($p) => !empty($p['trabajador_id']))
            ->values();

        /**
         * =========================
         * B) FICHAJES ANTIGUOS (BD trabajadores / mysql_polifonia)
         * =========================
         */
        $fichajesTrabajadores = DB::connection('mysql_trabajadores')
            ->table('fichar') // ⚠️ ajusta si el nombre real difiere
            ->select(['user_id', 'tipo', 'fecha_hora', 'bienestar'])
            ->whereIn('user_id', $trabajadores->pluck('id'))
            ->whereBetween('fecha_hora', [$start, $end])
            ->orderBy('fecha_hora')
            ->get()
            ->map(function ($f) {
                $tipo = strtoupper((string)($f->tipo ?? ''));

                $type = match ($tipo) {
                    'I', 'IN', 'ENTRADA' => 'in',
                    'F', 'OUT', 'SALIDA' => 'out',
                    default              => 'fichaje',
                };

                return [
                    'origen'        => 'trabajadores',
                    'trabajador_id' => (int)$f->user_id,
                    'type'          => $type,
                    'hora'          => $f->fecha_hora
                        ? Carbon::parse($f->fecha_hora)->format('H:i')
                        : '—',
                    'datetime'      => $f->fecha_hora,
                    'mood'          => is_null($f->bienestar) ? null : (int)$f->bienestar,
                    'is_manual'     => null,
                    'note'          => null,
                    'raw_user_id'   => null,
                ];
            })
            ->values();

        /**
         * =========================
         * UNIFICAR AMBAS FUENTES
         * =========================
         */
        $allPunches = $punchesFichajes
            ->concat($fichajesTrabajadores)
            ->sortBy('datetime')
            ->values();

        $punchesByTrabajador = $allPunches->groupBy('trabajador_id');

        /**
         * =========================
         * 4) CONSTRUIR FILAS (por trabajador)
         * =========================
         */
        $rows = $trabajadores->map(function ($t) use ($mapTrabajadorToFichajes, $punchesByTrabajador, $date, $absenceMap) {
            $trabajadorId = (int)$t->id;
            $fu = $mapTrabajadorToFichajes->get($trabajadorId); // user_id en fichajes (puede ser null)

            $userPunches = $punchesByTrabajador->get($trabajadorId) ?? collect();

            $entradas = $userPunches->filter(fn ($p) => ($p['type'] ?? '') === 'in');
            $salidas  = $userPunches->filter(fn ($p) => ($p['type'] ?? '') === 'out');

            $firstIn = $entradas->first()['hora'] ?? null;
            $lastOut = $salidas->last()['hora'] ?? null;

            $absenceTipo = $absenceMap->get($trabajadorId); // 'V'|'P'|'B'|null

            return (object)[
                'date'          => $date,
                'trabajador_id' => $trabajadorId,
                'nombre'        => $t->nombre,
                'email'         => $t->email,
                'activo'        => (int)($t->activo ?? 0),

                // ✅ NUEVO: causa de no fichar (si aplica)
                'absence_tipo'  => $absenceTipo,

                // aunque no esté vinculado en "fichajes", puede fichar en la BD vieja
                'vinculado_fichajes' => (bool)$fu,
                'user_fichajes_id'   => $fu,

                'count'    => $userPunches->count(),
                'first_in' => $firstIn,
                'last_out' => $lastOut,

                'solo_entrada' => $entradas->count() > 0 && $salidas->count() === 0,
                'solo_salida'  => $salidas->count() > 0 && $entradas->count() === 0,

                'punches' => $userPunches->map(function ($p) {
                    $type = (string)($p['type'] ?? '');
                    return [
                        'type'      => $type === 'in' ? 'entrada' : ($type === 'out' ? 'salida' : $type),
                        'hora'      => $p['hora'] ?? '—',
                        'mood'      => $p['mood'] ?? null,
                        'is_manual' => $p['is_manual'] ?? null,
                        'note'      => $p['note'] ?? null,
                        'origen'    => $p['origen'] ?? null,
                    ];
                })->values()->all(),
            ];
        })->values();

        /**
         * =========================
         * 5) STATS (sin fichaje real vs en ausencia)
         * =========================
         */
        $conFichaje  = $rows->filter(fn ($r) => ($r->count ?? 0) > 0)->count();
        $soloEntrada = $rows->filter(fn ($r) => (bool)($r->solo_entrada ?? false))->count();
        $soloSalida  = $rows->filter(fn ($r) => (bool)($r->solo_salida ?? false))->count();

        $enAusencia = $rows->filter(fn ($r) => ($r->count ?? 0) === 0 && !empty($r->absence_tipo))->count();
        $sinFichajeReal = $rows->filter(fn ($r) => ($r->count ?? 0) === 0 && empty($r->absence_tipo))->count();

        $stats = [
            'total'        => $rows->count(),
            'con_fichaje'  => $conFichaje,
            'sin_fichaje'  => $sinFichajeReal, // ✅ ahora es “no fichó” real
            'en_ausencia'  => $enAusencia,     // ✅ opcional (para una tarjeta extra)
            'solo_entrada' => $soloEntrada,
            'solo_salida'  => $soloSalida,
        ];

        return [
            'date'    => $date,
            'groupId' => $groupId,
            'estado'  => $estado,
            'rows'    => $rows,
            'stats'   => $stats,
        ];
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $month        = $request->input('month') ?: now()->format('Y-m'); // YYYY-MM
        $groupId      = $request->input('grupo');                         // opcional
        $estado       = (string)($request->input('estado', '') ?? '');     // '' | activo | inactivo
        $trabajadorId = $request->input('trabajador_id');                 // opcional (individual)

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        if (!in_array($estado, ['', 'activo', 'inactivo'], true)) {
            $estado = '';
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // 1) trabajadores base
        $trabajadores = $this->getTrabajadoresFiltrados($groupId, $estado);

        if ($trabajadorId) {
            $trabajadores = $trabajadores
                ->filter(fn($t) => (int)$t->id === (int)$trabajadorId)
                ->values();
        }

        // 2) map trabajador -> user_fichaje_id
        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadores->pluck('id'))
            ->get(['trabajador_id', 'user_fichaje_id']);

        $mapTrabajadorToFichajes = $vinculos
            ->filter(fn($v) => !empty($v->user_fichaje_id))
            ->mapWithKeys(fn($v) => [(int)$v->trabajador_id => (int)$v->user_fichaje_id]);

        $fichajesUserIds = $mapTrabajadorToFichajes->values()->unique()->values();
        $mapFichajesToTrabajador = $mapTrabajadorToFichajes->flip(); // [user_fichaje_id => trabajador_id]

        // 3) punches “nuevos” (mysql_fichajes) del mes
        $punchesFichajes = collect();

        if ($fichajesUserIds->isNotEmpty()) {
            $raw = DB::connection('mysql_fichajes')
                ->table('punches')
                ->select(['user_id', 'type', 'happened_at'])
                ->whereIn('user_id', $fichajesUserIds->all())
                ->whereBetween('happened_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                ->orderBy('happened_at')
                ->get();

            $punchesFichajes = collect($raw)->map(function ($p) use ($mapFichajesToTrabajador) {
                $type = strtolower((string)$p->type);
                if ($type !== 'in' && $type !== 'out') $type = 'other';

                $tid = $mapFichajesToTrabajador->get((int)$p->user_id);

                return [
                    'origen'        => 'fichajes',
                    'trabajador_id' => $tid ? (int)$tid : null,
                    'type'          => $type,
                    'datetime'      => Carbon::parse($p->happened_at),
                ];
            })->filter(fn($p) => !empty($p['trabajador_id']))->values();
        }

        // 4) punches “viejos” (mysql_trabajadores) del mes
        $punchesTrab = collect();

        if ($trabajadores->isNotEmpty()) {
            $fk = $this->detectFicharTrabajadorFkColumn(); // ✅ AUTO-DETECTA la columna real

            $rawOld = DB::connection('mysql_trabajadores')
                ->table('fichar')
                ->select([$fk, 'tipo', 'fecha_hora'])
                ->whereIn($fk, $trabajadores->pluck('id')->all())
                ->whereBetween('fecha_hora', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                ->orderBy('fecha_hora')
                ->get();

            $punchesTrab = collect($rawOld)->map(function ($f) use ($fk) {
                $tipo = strtoupper((string)$f->tipo);
                $type = match ($tipo) {
                    'I', 'IN', 'ENTRADA' => 'in',
                    'F', 'OUT', 'SALIDA' => 'out',
                    default => 'other',
                };

                return [
                    'origen'        => 'trabajadores',
                    'trabajador_id' => (int)($f->{$fk}),
                    'type'          => $type,
                    'datetime'      => Carbon::parse($f->fecha_hora),
                ];
            });
        }

        // 5) unificar
        $allPunches = $punchesFichajes
            ->concat($punchesTrab)
            ->sortBy(fn($p) => $p['datetime']->timestamp)
            ->values();

        $punchesByTrabajador = $allPunches->groupBy('trabajador_id');

        // 6) crear Excel (1 hoja por trabajador)
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($trabajadores as $t) {
            $tid = (int)$t->id;

            $sheetName = $this->safeSheetName($t->nombre ?: ('Trabajador_'.$tid));
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            // ===== Cabecera superior =====
            $sheet->setCellValue('A1', 'Trabajador');
            $sheet->setCellValue('B1', $t->nombre);
            $sheet->setCellValue('A2', 'Email');
            $sheet->setCellValue('B2', $t->email);
            $sheet->setCellValue('A3', 'Mes');
            $sheet->setCellValue('B3', $month);

            // Estilo cabecera superior
            $sheet->getStyle('A1:A3')->getFont()->setBold(true);
            $sheet->getStyle('A1:B3')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A1:B3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

            // Línea de separación
            $sheet->setCellValue('A4', '');
            $sheet->mergeCells('A4:E4');

            // ===== Cabecera tabla =====
            $sheet->fromArray(['Fecha', 'Entrada', 'Salida', 'Horas (hh:mm)', 'Origen'], null, 'A5');

            // Estilo cabecera tabla
            $sheet->getStyle('A5:E5')->getFont()->setBold(true);
            $sheet->getStyle('A5:E5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A5:E5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A5:E5')->getFill()->getStartColor()->setARGB('FF15803D'); // verde
            $sheet->getStyle('A5:E5')->getFont()->getColor()->setARGB('FFFFFFFF'); // blanco

            // Congelar y filtros
            $sheet->freezePane('A6');
            $sheet->setAutoFilter('A5:E5');

            $rows = [];
            $totalMinutes = 0;

            $punches = collect($punchesByTrabajador->get($tid) ?? []);
            $byDay = $punches->groupBy(fn($p) => $p['datetime']->format('Y-m-d'));

            $dayCursor = $start->copy();
            while ($dayCursor <= $end) {
                $dayKey = $dayCursor->format('Y-m-d');
                $dayPunches = collect($byDay->get($dayKey) ?? [])
                    ->sortBy(fn($p) => $p['datetime']->timestamp)
                    ->values();

                $firstIn = optional($dayPunches->firstWhere('type', 'in'))['datetime'] ?? null;
                $lastOut = optional($dayPunches->reverse()->firstWhere('type', 'out'))['datetime'] ?? null;

                $shiftType = $t->turno ?? 'office'; // campaign/office/intensive

                // ✅ Evitar 00:00 cuando solo hay entrada (sin salida):
                // Solo calculamos horas si existe OUT.
                $worked = null;
                if ($lastOut) {
                    $worked = $this->calcWorkedMinutesWithBreaks($dayPunches, $shiftType);
                    $totalMinutes += $worked;
                }

                $origins = $dayPunches->pluck('origen')->unique()->values()->all();
                $originLabel = count($origins) > 1 ? 'mixto' : ($origins[0] ?? '—');

                $rows[] = [
                    $dayKey,
                    $firstIn ? $firstIn->format('H:i') : '—',
                    $lastOut ? $lastOut->format('H:i') : '—',
                    $worked === null ? '—' : $this->fmtMinutes($worked),
                    $originLabel,
                ];

                $dayCursor->addDay();
            }

            $startRow = 6;
            $sheet->fromArray($rows, null, 'A'.$startRow);

            $lastDataRow = $startRow + count($rows) - 1;

            // Bordes + alineación tabla
            if ($lastDataRow >= $startRow) {
                $range = "A5:E{$lastDataRow}";
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->getColor()->setARGB('FFE2E8F0'); // gris suave

                $sheet->getStyle("A{$startRow}:A{$lastDataRow}")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("B{$startRow}:D{$lastDataRow}")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("E{$startRow}:E{$lastDataRow}")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Resaltar filas:
                // - Entrada sin salida => ámbar suave
                // - No fichó (sin in y sin out) => rojo suave
                for ($r = $startRow; $r <= $lastDataRow; $r++) {
                    $in  = (string)$sheet->getCell("B{$r}")->getValue();
                    $out = (string)$sheet->getCell("C{$r}")->getValue();

                    if ($in !== '—' && $out === '—') {
                        $sheet->getStyle("A{$r}:E{$r}")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle("A{$r}:E{$r}")->getFill()->getStartColor()->setARGB('FFFFF7ED'); // ámbar suave
                    } elseif ($in === '—' && $out === '—') {
                        $sheet->getStyle("A{$r}:E{$r}")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle("A{$r}:E{$r}")->getFill()->getStartColor()->setARGB('FFFFF1F2'); // rojo suave
                    }
                }
            }

            // TOTAL bonito
            $totalRow = $lastDataRow + 2;
            $sheet->setCellValue("A{$totalRow}", 'TOTAL');
            $sheet->setCellValue("D{$totalRow}", $this->fmtMinutes($totalMinutes));

            $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
            $sheet->getStyle("A{$totalRow}:E{$totalRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$totalRow}:E{$totalRow}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("A{$totalRow}:E{$totalRow}")->getFill()->getStartColor()->setARGB('FFF1F5F9'); // gris suave
            $sheet->getStyle("D{$totalRow}")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // AutoSize columnas
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $groupName = null;

        if ($groupId) {
            $groupName = DB::connection('mysql')
                ->table('groups')
                ->where('id', (int)$groupId)
                ->value('name');

            if ($groupName) {
                $groupName = strtolower($groupName);
                $groupName = preg_replace('/[^a-z0-9_-]+/i', '_', $groupName);
                $groupName = trim($groupName, '_');
            }
        }

        $fileName = 'horas_'.$month;

        if ($trabajadorId) {
            $fileName .= '_trabajador_'.$trabajadorId;
        }

        if ($groupId) {
            $fileName .= '_grupo_'.($groupName ?: $groupId); // ✅ ahora sale injerto en vez de 1 si existe nombre
        }

        $fileName .= '.xlsx';

        $tmpPath = storage_path('app/'.$fileName);
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }


    // ==========================
    // Helpers
    // ==========================

    private function getTrabajadoresFiltrados($groupId, string $estado): Collection
    {
        $q = TrabajadorPolifonia::query()
            ->select(['id','nombre','email','activo']) // (si tienes "turno" en este modelo, mejor añadirlo aquí)
            ->orderBy('nombre');

        if ($estado === 'activo')   $q->where('activo', 1);
        if ($estado === 'inactivo') $q->where('activo', 0);

        if ($groupId !== null && $groupId !== '') {
            $ids = DB::connection('mysql')
                ->table('group_trabajador')
                ->where('group_id', (int)$groupId)
                ->pluck('trabajador_id')
                ->map(fn($v) => (int)$v)
                ->all();

            if (empty($ids)) return collect();
            $q->whereIn('id', $ids);
        }

        return collect($q->get());
    }

    /**
     * Detecta la columna FK al trabajador en mysql_trabajadores.fichar
     * para evitar “Unknown column ...”.
     */
    private function detectFicharTrabajadorFkColumn(): string
    {
        $cols = DB::connection('mysql_trabajadores')->select('DESCRIBE fichar');
        $names = array_map(fn($r) => strtolower((string)($r->Field ?? '')), $cols);

        // candidatos típicos (ordénalos por probabilidad en tu proyecto)
        $candidates = [
            'trabajador_id',
            'id_trabajador',
            'idtrabajador',
            'usuario_id',
            'id_usuario',
            'user_id',
            'iduser',
        ];

        foreach ($candidates as $c) {
            if (in_array($c, $names, true)) {
                return $c;
            }
        }

        // fallback: intenta un campo que contenga "trabaj"
        foreach ($names as $n) {
            if (str_contains($n, 'trabaj')) return $n;
        }

        throw new \RuntimeException("No se pudo detectar la columna FK de trabajador en mysql_trabajadores.fichar. Columnas: ".implode(',', $names));
    }

    private function calcWorkedMinutes(Collection $punches): int
    {
        $mins = 0;
        $openIn = null;

        foreach ($punches as $p) {
            if (($p['type'] ?? '') === 'in') {
                $openIn = $p['datetime'];
                continue;
            }
            if (($p['type'] ?? '') === 'out' && $openIn) {
                $diff = $openIn->diffInMinutes($p['datetime'], false);
                if ($diff > 0) $mins += $diff;
                $openIn = null;
            }
        }

        return $mins;
    }

    private function fmtMinutes(int $minutes): string
    {
        $h = intdiv(max(0, $minutes), 60);
        $m = max(0, $minutes) % 60;
        return str_pad((string)$h, 2, '0', STR_PAD_LEFT).':'.str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    }

    private function safeSheetName(string $name): string
    {
        $clean = preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $name);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') $clean = 'Trabajador';
        return mb_substr($clean, 0, 31);
    }

    private function calcWorkedMinutesWithBreaks(Collection $punches, string $shiftType): int
    {
        // 1) intervalos trabajados (IN -> OUT)
        $workedIntervals = $this->buildWorkedIntervals($punches);
        if (empty($workedIntervals)) return 0;

        // 2) bruto
        $gross = 0;
        foreach ($workedIntervals as [$s, $e]) {
            $diff = $s->diffInMinutes($e, false);
            if ($diff > 0) $gross += $diff;
        }

        // 3) descansos (incluye regla campaign sin comida si acaban <= 16:00)
        $breaks = $this->getBreakWindowsForShift($shiftType, $workedIntervals);

        $deduct = 0;
        foreach ($breaks as [$bs, $be]) {
            $deduct += $this->overlapMinutesWithIntervals($workedIntervals, $bs, $be);
        }

        return max(0, $gross - $deduct);
    }

    /**
     * Devuelve intervalos trabajados por pares IN->OUT en orden.
     * Ignora OUT sin IN y deja IN abierto sin OUT sin sumar.
     * @return array{0:Carbon,1:Carbon}[]
     */
    private function buildWorkedIntervals(Collection $punches): array
    {
        $sorted = $punches
            ->filter(fn($p) => isset($p['datetime']))
            ->sortBy(fn($p) => $p['datetime']->timestamp)
            ->values();

        $intervals = [];
        $openIn = null;

        foreach ($sorted as $p) {
            $type = $p['type'] ?? null;
            if ($type === 'in') {
                $openIn = $p['datetime'];
                continue;
            }
            if ($type === 'out' && $openIn) {
                $out = $p['datetime'];
                if ($out->gt($openIn)) {
                    $intervals[] = [$openIn->copy(), $out->copy()];
                }
                $openIn = null;
            }
        }

        return $intervals;
    }

    /**
     * Ventanas de descanso del turno.
     * IMPORTANTE para intensive: solo aplica UNA (mañana o tarde) según solape real con trabajo.
     *
     * @param array $workedIntervals [[start,end],...]
     * @return array{0:Carbon,1:Carbon}[]  ventanas a descontar
     */
    private function getBreakWindowsForShift(string $shiftType, array $workedIntervals): array
    {
        $day = $workedIntervals[0][0]->copy()->startOfDay();

        $make = function(string $hhmmA, string $hhmmB) use ($day) {
            [$ha,$ma] = array_map('intval', explode(':', $hhmmA));
            [$hb,$mb] = array_map('intval', explode(':', $hhmmB));
            $a = $day->copy()->setTime($ha,$ma,0);
            $b = $day->copy()->setTime($hb,$mb,0);
            return [$a,$b];
        };

        $shiftType = strtolower(trim($shiftType));

        // ✅ último OUT del día (para decidir si descuentas comida)
        $lastOut = null;
        foreach (array_reverse($workedIntervals) as [$s,$e]) { $lastOut = $e; break; }
        $lastOutHm = $lastOut ? $lastOut->format('H:i') : null;

        if ($shiftType === 'campaign') {
            $out = [
                $make('10:00','10:30'), // almuerzo
            ];

            // ✅ comida 14-15 SOLO si terminan después de 16:00
            if ($lastOutHm && $lastOutHm > '16:00') {
                $out[] = $make('14:00','15:00');
            }

            return $out;
        }

        if ($shiftType === 'office') {
            $out = [
                $make('10:00','10:30'),
            ];

            // si terminan pronto, no quites la comida
            if ($lastOutHm && $lastOutHm > '16:00') {
                $out[] = $make('14:00','16:00');
            }

            return $out;
        }

        if ($shiftType === 'intensive') {
            $morning = $make('10:00','10:30');
            $evening = $make('19:00','19:30');

            $morningOverlap = $this->overlapMinutesWithIntervals($workedIntervals, $morning[0], $morning[1]);
            $eveningOverlap = $this->overlapMinutesWithIntervals($workedIntervals, $evening[0], $evening[1]);

            if ($morningOverlap > 0 || $eveningOverlap > 0) {
                return ($eveningOverlap > $morningOverlap) ? [$evening] : [$morning];
            }

            return [];
        }

        return [];
    }

    /**
     * Minutos de solape entre una ventana [bs,be] y TODOS los intervalos trabajados.
     */
    private function overlapMinutesWithIntervals(array $workedIntervals, Carbon $bs, Carbon $be): int
    {
        $sum = 0;

        foreach ($workedIntervals as [$s, $e]) {
            // max(start), min(end)
            $start = $s->greaterThan($bs) ? $s : $bs;
            $end   = $e->lessThan($be) ? $e : $be;

            if ($end->gt($start)) {
                $sum += $start->diffInMinutes($end);
            }
        }

        return $sum;
    }
}
