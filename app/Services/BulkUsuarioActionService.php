<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BulkUsuarioActionService
{
    public function __construct(
        private readonly CatalogosService $catalogos,
        private readonly VinculacionService $vinculacion,
    ) {}

    public function setActivo(array $trabajadorIds, int $activo): int
    {
        $ids = $this->normalizeIds($trabajadorIds);

        if (empty($ids)) {
            return 0;
        }

        return TrabajadorPolifonia::on('mysql_polifonia')
            ->whereIn('id', $ids)
            ->update(['activo' => $activo === 1 ? 1 : 0]);
    }

    public function setActivoDetailed(array $trabajadorIds, int $activo): array
    {
        $ids = $this->normalizeIds($trabajadorIds);
        $target = $activo === 1 ? 1 : 0;

        $result = [
            'processed' => count($ids),
            'updated' => 0,
            'ok' => [],
            'skipped' => [],
            'failed' => [],
        ];

        if (empty($ids)) {
            return $result;
        }

        $workers = TrabajadorPolifonia::on('mysql_polifonia')
            ->select(['id', 'nombre', 'activo'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            /** @var TrabajadorPolifonia|null $worker */
            $worker = $workers->get($id);

            if (!$worker) {
                $result['failed'][] = ['id' => $id, 'name' => 'N/D', 'reason' => 'Trabajador no encontrado'];
                continue;
            }

            if ((int) $worker->activo === $target) {
                $result['skipped'][] = [
                    'id' => $id,
                    'name' => (string) ($worker->nombre ?? 'N/D'),
                    'reason' => $target === 1 ? 'Ya estaba activo' : 'Ya estaba inactivo',
                ];
                continue;
            }

            $updated = TrabajadorPolifonia::on('mysql_polifonia')
                ->where('id', $id)
                ->update(['activo' => $target]);

            if ($updated > 0) {
                $result['updated']++;
                $result['ok'][] = [
                    'id' => $id,
                    'name' => (string) ($worker->nombre ?? 'N/D'),
                    'reason' => $target === 1 ? 'Activado' : 'Desactivado',
                ];
            } else {
                $result['failed'][] = [
                    'id' => $id,
                    'name' => (string) ($worker->nombre ?? 'N/D'),
                    'reason' => 'No se pudo actualizar',
                ];
            }
        }

        return $result;
    }

    public function autoLinkByEmail(array $trabajadorIds): array
    {
        $ids = $this->normalizeIds($trabajadorIds);

        if (empty($ids)) {
            return [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'no_email' => 0,
                'no_match' => 0,
                'errors' => 0,
                'ok' => [],
                'skipped' => [],
                'failed' => [],
            ];
        }

        $trabajadores = TrabajadorPolifonia::on('mysql_polifonia')
            ->select(['id', 'nombre', 'email'])
            ->whereIn('id', $ids)
            ->get();

        $catalogos = $this->catalogos->getCatalogos();

        $mapUsuarios = $this->indexByEmail($catalogos['usuarios'] ?? collect(), 'email');
        $mapPluton = $this->indexByEmail($catalogos['usuariosPluton'] ?? collect(), 'email');
        $mapBuscadorUsers = $this->indexByEmail($catalogos['usuariosBuscador'] ?? collect(), 'email');
        $mapBuscadorWorkers = $this->indexByEmail($catalogos['trabajadoresBuscador'] ?? collect(), 'email');
        $mapCronos = $this->indexByEmail($catalogos['userCronos'] ?? collect(), 'email');
        $mapSemillas = $this->indexByEmail($catalogos['userSemillas'] ?? collect(), 'email');
        $mapStore = $this->indexByEmail($catalogos['userStore'] ?? collect(), 'email');
        $mapZeus = $this->indexByEmail($catalogos['userZeus'] ?? collect(), 'email');
        $mapFichajes = $this->indexByEmail($catalogos['usuariosFichajes'] ?? collect(), 'email');

        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'no_email' => 0,
            'no_match' => 0,
            'errors' => 0,
            'ok' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($trabajadores as $trabajador) {
            $stats['processed']++;

            $email = $this->normalizeEmail($trabajador->email ?? null);
            if (!$email) {
                $stats['no_email']++;
                $stats['skipped'][] = [
                    'id' => (int) $trabajador->id,
                    'name' => (string) ($trabajador->nombre ?? ('Trabajador #' . (int) $trabajador->id)),
                    'reason' => 'Sin email',
                ];
                continue;
            }

            $payload = [
                'trabajador_id' => (int) $trabajador->id,
                'usuario_id' => $mapUsuarios[$email] ?? null,
                'pluton_id' => $mapPluton[$email] ?? null,
                'user_buscador_id' => $mapBuscadorUsers[$email] ?? null,
                'worker_buscador_id' => $mapBuscadorWorkers[$email] ?? null,
                'user_cronos_id' => $mapCronos[$email] ?? null,
                'user_semillas_id' => $mapSemillas[$email] ?? null,
                'user_store_id' => $mapStore[$email] ?? null,
                'user_zeus_id' => $mapZeus[$email] ?? null,
                'user_fichaje_id' => $mapFichajes[$email] ?? null,
            ];

            $hasAtLeastOneMatch = collect($payload)
                ->except('trabajador_id')
                ->filter(fn ($value) => !empty($value))
                ->isNotEmpty();

            if (!$hasAtLeastOneMatch) {
                $stats['no_match']++;
                $stats['skipped'][] = [
                    'id' => (int) $trabajador->id,
                    'name' => (string) ($trabajador->nombre ?? ('Trabajador #' . (int) $trabajador->id)),
                    'reason' => 'Sin coincidencias por email',
                ];
                continue;
            }

            $existing = UsuarioVinculado::query()
                ->where('trabajador_id', $payload['trabajador_id'])
                ->when(!empty($payload['usuario_id']), fn ($q) => $q->orWhere('usuario_id', $payload['usuario_id']))
                ->when(!empty($payload['pluton_id']), fn ($q) => $q->orWhere('pluton_id', $payload['pluton_id']))
                ->first();

            $payload['uuid'] = $existing?->uuid ?? (string) Str::uuid();

            try {
                $this->vinculacion->validateExternalIds($payload);
                $result = $this->vinculacion->save($payload);

                if ($existing || !$result->wasRecentlyCreated) {
                    $stats['updated']++;
                    $stats['ok'][] = [
                        'id' => (int) $trabajador->id,
                        'name' => (string) ($trabajador->nombre ?? ('Trabajador #' . (int) $trabajador->id)),
                        'reason' => 'Vinculo actualizado',
                    ];
                } else {
                    $stats['created']++;
                    $stats['ok'][] = [
                        'id' => (int) $trabajador->id,
                        'name' => (string) ($trabajador->nombre ?? ('Trabajador #' . (int) $trabajador->id)),
                        'reason' => 'Vinculo creado',
                    ];
                }
            } catch (\Throwable) {
                $stats['errors']++;
                $stats['failed'][] = [
                    'id' => (int) $trabajador->id,
                    'name' => (string) ($trabajador->nombre ?? ('Trabajador #' . (int) $trabajador->id)),
                    'reason' => 'Error al validar o guardar vinculo',
                ];
            }
        }

        return $stats;
    }

    private function indexByEmail(Collection $rows, string $emailField): array
    {
        return $rows
            ->mapWithKeys(function ($row) use ($emailField) {
                $email = $this->normalizeEmail(data_get($row, $emailField));
                $id = (int) (data_get($row, 'id') ?? 0);

                if (!$email || $id <= 0) {
                    return [];
                }

                return [$email => $id];
            })
            ->all();
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeEmail(?string $email): ?string
    {
        $value = mb_strtolower(trim((string) $email));
        return $value !== '' ? $value : null;
    }
}

