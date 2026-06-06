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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CatalogosService
{
    /** TTL en segundos para los catálogos (1 hora por defecto). */
    private const CACHE_TTL = 3600;

    /**
     * Devuelve los catálogos cacheados con columnas mínimas.
     * ⚡ Reduce de 10 queries por request a 0 cuando el cache está caliente.
     */
    public function getCatalogos(): array
    {
        return [
            'usuarios'             => $this->cached('cat:usuarios', fn () =>
                Usuario::select(['id', 'nombre', 'email'])->orderBy('nombre')->get()),

            'trabajadores'         => $this->cached('cat:trabajadores', fn () =>
                TrabajadorPolifonia::on('mysql_polifonia')->select(['id', 'nombre', 'email'])->orderBy('nombre')->get()),

            'usuariosPluton'       => $this->cached('cat:usuariosPluton', fn () =>
                UserPluton::on('mysql_pluton')->select(['id', 'nombre', 'email'])->orderBy('nombre')->get()),

            'usuariosBuscador'     => $this->cached('cat:usuariosBuscador', fn () =>
                UserBuscador::on('mysql_buscador')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'trabajadoresBuscador' => $this->cached('cat:trabajadoresBuscador', fn () =>
                WorkerBuscador::on('mysql_buscador')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'userCronos'           => $this->cached('cat:userCronos', fn () =>
                UserCronos::on('mysql_cronos')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'userSemillas'         => $this->cached('cat:userSemillas', fn () =>
                UserSemillas::on('mysql_semillas')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'userStore'            => $this->cached('cat:userStore', fn () =>
                UserStore::on('mysql_store')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'userZeus'             => $this->cached('cat:userZeus', fn () =>
                UserZeus::on('mysql_zeus')->select(['id', 'name', 'email'])->orderBy('name')->get()),

            'usuariosFichajes'     => $this->cached('cat:usuariosFichajes', fn () =>
                UserFichaje::on('mysql_fichajes')->select(['id', 'name', 'email'])->orderBy('name')->get()),
        ];
    }

    /**
     * Invalida todos los catálogos del cache.
     * Llama a este método en los Observers / después de crear/editar/borrar usuarios.
     */
    public static function invalidate(): void
    {
        $keys = [
            'cat:usuarios', 'cat:trabajadores', 'cat:usuariosPluton',
            'cat:usuariosBuscador', 'cat:trabajadoresBuscador', 'cat:userCronos',
            'cat:userSemillas', 'cat:userStore', 'cat:userZeus', 'cat:usuariosFichajes',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info('[CatalogosService] Cache de catálogos invalidado.');
    }

    /**
     * Envuelve la consulta en Cache::remember + safe().
     */
    private function cached(string $key, callable $query): Collection
    {
        return Cache::remember($key, self::CACHE_TTL, fn () => $this->safe($query));
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
