<?php

namespace App\Services;

use App\Models\UserFichaje;
use App\Models\Usuario;
use App\Models\TrabajadorPolifonia;
use App\Models\UserPluton;
use App\Models\UserBuscador;
use App\Models\WorkerBuscador;
use App\Models\UserCronos;
use App\Models\UserSemillas;
use App\Models\UserStore;
use App\Models\UserZeus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CatalogosService
{
    public function getCatalogos(): array
    {
        return [
            'usuarios'             => $this->safe(fn () => Usuario::orderBy('nombre')->get()),
            'trabajadores'         => $this->safe(fn () => TrabajadorPolifonia::on('mysql_polifonia')->orderBy('nombre')->get()),
            'usuariosPluton'       => $this->safe(fn () => UserPluton::on('mysql_pluton')->orderBy('nombre')->get()),
            'usuariosBuscador'     => $this->safe(fn () => UserBuscador::on('mysql_buscador')->orderBy('name')->get()),
            'trabajadoresBuscador' => $this->safe(fn () => WorkerBuscador::on('mysql_buscador')->orderBy('name')->get()),
            'userCronos'           => $this->safe(fn () => UserCronos::on('mysql_cronos')->orderBy('name')->get()),
            'userSemillas'         => $this->safe(fn () => UserSemillas::on('mysql_semillas')->orderBy('name')->get()),
            'userStore'            => $this->safe(fn () => UserStore::on('mysql_store')->orderBy('name')->get()),
            'userZeus'             => $this->safe(fn () => UserZeus::on('mysql_zeus')->orderBy('name')->get()),
            'usuariosFichajes'     => $this->safe(fn () => UserFichaje::on('mysql_fichajes')->orderBy('name')->get()),
        ];
    }

    /**
     * Ejecuta la consulta y devuelve colección vacía si la tabla o conexión no existe.
     */
    private function safe(callable $query): Collection
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            Log::warning('[CatalogosService] Catálogo no disponible: ' . $e->getMessage());
            return collect();
        }
    }
}
