<?php

namespace App\Services;

use App\Models\TrabajadorDia;
use App\Models\TrabajadorPolifonia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $bucketYear = $data['tipo'] === 'V'
            ? (int)($data['bucket_year'] ?? $data['calendar_year'])
            : (int)$data['calendar_year'];

        $period = CarbonPeriod::create($data['from'], $data['to']);
        $dates = collect($period)->map(fn($d) => $d->format('Y-m-d'))->values();

        DB::connection('mysql_polifonia')->transaction(function () use ($trabajadorId, $data, $dates, $bucketYear) {

            if ($data['mode'] === 'remove') {
                DB::connection('mysql_polifonia')->table('trabajadores_dias')
                    ->where('trabajador_id', $trabajadorId)
                    ->where('tipo', $data['tipo'])
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
                    'tipo' => $data['tipo'],
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

    public function streamPdfVacaciones(int $trabajadorId, int $vacationYear, string $tipo): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tipo = strtoupper($tipo); // V|P|B

        $t = TrabajadorPolifonia::query()->findOrFail($trabajadorId);

        $diasBase = TrabajadorDia::query()
            ->where('trabajador_id', $trabajadorId)
            ->where('vacation_year', $vacationYear)
            ->where('tipo', $tipo)
            ->orderBy('fecha')
            ->pluck('fecha');

        if ($diasBase->isEmpty()) {
            abort(404, 'No hay ausencias registradas');
        }

        $dias = $this->extendConsecutivosPosteriores($trabajadorId, $tipo, $diasBase);

        $rangos = $this->toRangos($dias);

        $tipoTexto = match ($tipo) {
            'V' => 'vacaciones',
            'P' => 'permisos',
            'B' => 'bajas',
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

        $pdf = Pdf::loadView('pdfs.pdp_vacaciones', $data);
        return $pdf->stream("{$tipoTexto}_{$trabajadorId}_{$vacationYear}.pdf");
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
        ];

        // sets para contar días únicos por tipo
        $seen = [
            'vacaciones' => [],
            'permiso'    => [],
            'baja'       => [],
        ];

        foreach ($rows as $r) {
            $key = $r->tipo === 'V' ? 'vacaciones' : ($r->tipo === 'P' ? 'permiso' : 'baja');

            $fecha = is_string($r->fecha)
                ? $r->fecha
                : \Carbon\Carbon::parse($r->fecha)->format('Y-m-d');

            // bucket_year solo si aplica
            $bucket = $includeBucketYear ? (int)$r->vacation_year : null;

            // clave única: por fecha (y si incluyes bucket, diferéncialo)
            $uniqKey = $fecha; // ✅ contar siempre por día real (fecha)


            // si ya está, saltar (evita duplicados)
            if (isset($seen[$key][$uniqKey])) {
                continue;
            }
            $seen[$key][$uniqKey] = true;

            $item = ['fecha' => $fecha];
            if ($includeBucketYear) $item['bucket_year'] = $bucket;

            $out[$key]['items'][] = $item;
        }

        // count = nº de días únicos
        $out['vacaciones']['count'] = count($seen['vacaciones']);
        $out['permiso']['count']    = count($seen['permiso']);
        $out['baja']['count']       = count($seen['baja']);

        return $out;
    }

}
