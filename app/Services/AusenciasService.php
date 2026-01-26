<?php

namespace App\Services;

use App\Models\TrabajadorDia;
use App\Models\TrabajadorPolifonia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AusenciasService
{
    public function attachAusenciasPayload(Collection $items, int $calendarYear): Collection
    {
        return $items->map(function ($r) use ($calendarYear) {
            // ✅ IMPORTANTE: en el index queremos lo del AÑO CALENDARIO
            $r->ausencias = $this->buildAusenciasPayloadByCalendarYear((int)$r->id, $calendarYear);
            return $r;
        });
    }

    public function buildAusenciasPayload(int $trabajadorId, int $vacationYear): array
    {
        $rows = DB::connection('mysql_polifonia')->table('trabajadores_dias')
            ->select('fecha', 'tipo')
            ->where('trabajador_id', $trabajadorId)
            ->where('vacation_year', $vacationYear)
            ->get();

        return $this->formatPayload($rows, false);
    }

    public function buildAusenciasPayloadByCalendarYear(int $trabajadorId, int $calendarYear): array
    {
        $rows = DB::connection('mysql_polifonia')->table('trabajadores_dias')
            ->select('fecha', 'tipo', 'vacation_year')
            ->where('trabajador_id', $trabajadorId)
            ->whereYear('fecha', $calendarYear)
            ->get();

        return $this->formatPayload($rows, true);
    }

    public function storeDays(int $trabajadorId, array $data): array
    {
        $tipo = strtoupper($data['tipo']); // <-- NUEVO

        $bucketYear = $tipo === 'V'
            ? (int)($data['bucket_year'] ?? $data['calendar_year'])
            : (int)$data['calendar_year'];

        $period = CarbonPeriod::create($data['from'], $data['to']);
        $dates = collect($period)->map(fn($d) => $d->format('Y-m-d'))->values();

        DB::connection('mysql_polifonia')->transaction(function () use ($trabajadorId, $data, $dates, $bucketYear, $tipo) {

            if ($data['mode'] === 'remove') {
                DB::connection('mysql_polifonia')->table('trabajadores_dias')
                    ->where('trabajador_id', $trabajadorId)
                    ->where('tipo', $tipo)              // <-- usa $tipo
                    ->where('vacation_year', $bucketYear)
                    ->whereIn('fecha', $dates)
                    ->delete();
                return;
            }

            $occupied = DB::connection('mysql_polifonia')->table('trabajadores_dias')
                ->where('trabajador_id', $trabajadorId)
                ->whereIn('fecha', $dates)
                ->pluck('fecha')
                ->map(fn($d) => is_string($d) ? $d : $d->format('Y-m-d'))
                ->all();

            $occupiedSet = array_flip($occupied);

            $toInsert = [];
            foreach ($dates as $date) {
                if (isset($occupiedSet[$date])) continue;

                $toInsert[] = [
                    'trabajador_id' => $trabajadorId,
                    'fecha' => $date,
                    'vacation_year' => $bucketYear,
                    'tipo' => $tipo,                    // <-- usa $tipo
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($toInsert)) {
                DB::connection('mysql_polifonia')->table('trabajadores_dias')->insertOrIgnore($toInsert);
            }
        });

        return $this->buildAusenciasPayloadByCalendarYear($trabajadorId, (int)$data['calendar_year']);
    }

    public function streamPdfVacaciones(int $trabajadorId, int $vacationYear, string $tipo): Response
    {
        try {
            $tipoRaw = $tipo;
            $tipo = strtoupper($tipoRaw); // V|P|B|L

            // ✅ Validación fuerte del tipo
            if (!in_array($tipo, ['V','P','B','L'], true)) {
                Log::warning('[PDF STREAM] INVALID TIPO', [
                    'trabajadorId' => $trabajadorId,
                    'vacationYear' => $vacationYear,
                    'tipo_raw'     => $tipoRaw,
                    'tipo_norm'    => $tipo,
                ]);
                abort(400, 'Tipo de ausencia inválido');
            }

            Log::info('[PDF STREAM] ENTER', [
                'trabajadorId'   => $trabajadorId,
                'vacationYear'   => $vacationYear,
                'tipo_raw'       => $tipoRaw,
                'tipo_norm'      => $tipo,
                'full_url'       => request()?->fullUrl(),
                'query'          => request()?->query(),
                'env'            => app()->environment(),
                'db_default'     => config('database.default'),
            ]);

            // ✅ Confirma trabajador
            $t = TrabajadorPolifonia::query()->findOrFail($trabajadorId);

            Log::info('[PDF STREAM] trabajador OK', [
                'trabajadorId' => $t->id,
                'nombre'       => $t->nombre,
                'empresa'      => $t->empresa,
            ]);

            // ✅ Diagnóstico: qué hay en la tabla para ese trabajador (años)
            $yearsEnTabla = TrabajadorDia::query()
                ->where('trabajador_id', $trabajadorId)
                ->selectRaw('vacation_year, count(*) as c')
                ->groupBy('vacation_year')
                ->orderBy('vacation_year')
                ->pluck('c', 'vacation_year')
                ->toArray();

            Log::info('[PDF STREAM] years in TrabajadorDia', [
                'trabajadorId' => $trabajadorId,
                'years'        => $yearsEnTabla,
            ]);

            // ✅ Diagnóstico: qué tipos hay para ESE AÑO
            $tiposEnTabla = TrabajadorDia::query()
                ->where('trabajador_id', $trabajadorId)
                ->where('vacation_year', $vacationYear)
                ->selectRaw('tipo, count(*) as c')
                ->groupBy('tipo')
                ->pluck('c', 'tipo')
                ->toArray();

            Log::info('[PDF STREAM] tipos in year', [
                'trabajadorId' => $trabajadorId,
                'vacationYear' => $vacationYear,
                'tipos'        => $tiposEnTabla,
            ]);

            // ✅ Query real del PDF
            $diasBase = TrabajadorDia::query()
                ->where('trabajador_id', $trabajadorId)
                ->where('vacation_year', $vacationYear)
                ->where('tipo', $tipo)
                ->orderBy('fecha')
                ->pluck('fecha');

            Log::info('[PDF STREAM] diasBase result', [
                'trabajadorId' => $trabajadorId,
                'vacationYear' => $vacationYear,
                'tipo'         => $tipo,
                'count'        => $diasBase->count(),
                'sample'       => $diasBase->take(20)->values()->all(),
            ]);

            if ($diasBase->isEmpty()) {
                Log::warning('[PDF STREAM] EMPTY => abort 404', [
                    'trabajadorId' => $trabajadorId,
                    'vacationYear' => $vacationYear,
                    'tipo'         => $tipo,
                    'tiposEnTabla' => $tiposEnTabla,
                    'yearsEnTabla' => $yearsEnTabla,
                ]);

                abort(404, 'No hay ausencias registradas');
            }

            // ✅ Transformaciones
            $dias = $this->extendConsecutivosPosteriores($trabajadorId, $tipo, $diasBase);
            $rangos = $this->toRangos($dias);

            Log::info('[PDF STREAM] rangos built', [
                'trabajadorId' => $trabajadorId,
                'vacationYear' => $vacationYear,
                'tipo'         => $tipo,
                'dias_count'   => is_countable($dias) ? count($dias) : null,
                'rangos_count' => is_countable($rangos) ? count($rangos) : null,
                'rangos_head'  => array_slice($rangos ?? [], 0, 5),
            ]);

            $tipoTexto = match ($tipo) {
                'V' => 'vacaciones',
                'P' => 'permisos',
                'B' => 'bajas',
                'L' => 'libres',
                default => 'ausencias',
            };

            $data = [
                'empresa'    => $t->empresa,
                'trabajador' => $t->nombre,
                'dni'        => $t->nif,
                'tipo'       => $tipoTexto,
                'anyo'       => $vacationYear,
                'rangos'     => $rangos,
                'fecha'      => now()->format('d/m/Y'),
            ];

            Log::info('[PDF STREAM] rendering PDF', [
                'view'        => 'pdfs.pdp_vacaciones',
                'filename'    => "{$tipoTexto}_{$trabajadorId}_{$vacationYear}.pdf",
                'tipoTexto'   => $tipoTexto,
            ]);

            $pdf = Pdf::loadView('pdfs.pdp_vacaciones', $data);

            // ✅ IMPORTANTE: DomPDF stream devuelve Response (Illuminate\Http\Response)
            $resp = $pdf->stream("{$tipoTexto}_{$trabajadorId}_{$vacationYear}.pdf");

            Log::info('[PDF STREAM] DONE stream()', [
                'response_class' => is_object($resp) ? get_class($resp) : gettype($resp),
            ]);

            return $resp;

        } catch (\Throwable $e) {
            Log::error('[PDF STREAM] EXCEPTION', [
                'trabajadorId' => $trabajadorId ?? null,
                'vacationYear' => $vacationYear ?? null,
                'tipo'         => $tipo ?? null,
                'class'        => get_class($e),
                'msg'          => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace_head'   => array_slice($e->getTrace(), 0, 8),
            ]);

            throw $e;
        }
    }

    private function extendConsecutivosPosteriores(int $trabajadorId, string $tipo, Collection $diasBase): Collection
    {
        $last = Carbon::parse($diasBase->last())->startOfDay();

        $spill = TrabajadorDia::query()
            ->where('trabajador_id', $trabajadorId)
            ->where('tipo', $tipo)
            ->where('fecha', '>', $last->format('Y-m-d'))
            ->where('fecha', '<=', $last->copy()->addDays(31)->format('Y-m-d'))
            ->orderBy('fecha')
            ->pluck('fecha');

        $dias = $diasBase->map(fn($f) => Carbon::parse($f)->startOfDay());

        $cursor = $last->copy();
        foreach ($spill as $f) {
            $d = Carbon::parse($f)->startOfDay();
            if ($d->equalTo($cursor->copy()->addDay())) {
                $dias->push($d);
                $cursor = $d->copy();
            } else {
                break;
            }
        }

        return $dias;
    }

    private function toRangos(Collection $dias): array
    {
        $rangos = [];
        $inicio = null;
        $fin = null;

        foreach ($dias as $d) {
            if ($inicio === null) {
                $inicio = $d->copy();
                $fin    = $d->copy();
                continue;
            }

            if ($d->equalTo($fin->copy()->addDay())) {
                $fin = $d->copy();
            } else {
                $rangos[] = ['inicio' => $inicio->format('d/m/Y'), 'fin' => $fin->format('d/m/Y')];
                $inicio = $d->copy();
                $fin    = $d->copy();
            }
        }

        if ($inicio !== null) {
            $rangos[] = ['inicio' => $inicio->format('d/m/Y'), 'fin' => $fin->format('d/m/Y')];
        }

        return $rangos;
    }

    private function formatPayload($rows, bool $includeBucketYear): array
    {
        $out = [
            'vacaciones' => ['count' => 0, 'items' => []],
            'permiso'    => ['count' => 0, 'items' => []],
            'baja'       => ['count' => 0, 'items' => []],
            'libre'      => ['count' => 0, 'items' => []], // <-- NUEVO
        ];

        $seen = [
            'vacaciones' => [],
            'permiso'    => [],
            'baja'       => [],
            'libre'      => [], // <-- NUEVO
        ];

        foreach ($rows as $r) {
            $tipo = strtoupper($r->tipo);

            $key = match ($tipo) {
                'V' => 'vacaciones',
                'P' => 'permiso',
                'B' => 'baja',
                'L' => 'libre',
                default => null,
            };

            if ($key === null) {
                continue; // o registra/Log si quieres detectar tipos raros
            }

            $fecha = is_string($r->fecha)
                ? $r->fecha
                : \Carbon\Carbon::parse($r->fecha)->format('Y-m-d');

            $bucket = $includeBucketYear ? (int)$r->vacation_year : null;

            $uniqKey = $fecha;

            if (isset($seen[$key][$uniqKey])) {
                continue;
            }
            $seen[$key][$uniqKey] = true;

            $item = ['fecha' => $fecha];
            if ($includeBucketYear) $item['bucket_year'] = $bucket;

            $out[$key]['items'][] = $item;
        }

        $out['vacaciones']['count'] = count($seen['vacaciones']);
        $out['permiso']['count']    = count($seen['permiso']);
        $out['baja']['count']       = count($seen['baja']);
        $out['libre']['count']      = count($seen['libre']); // <-- NUEVO

        return $out;
    }

}
