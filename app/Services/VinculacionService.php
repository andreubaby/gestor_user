<?php

namespace App\Services;

use App\Models\UsuarioVinculado;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class VinculacionService
{
    public function clearPreselectionSession(): void
    {
        session()->forget([
            'usuario_preseleccionado',
            'trabajador_preseleccionado',
            'pluton_preseleccionado',
            'user_buscador_preseleccionado',
            'worker_buscador_preseleccionado',
            'user_cronos_preseleccionado',
            'user_semillas_preseleccionado',
            'user_store_preseleccionado',
            'user_zeus_preseleccionado',
            'user_fichaje_preseleccionado',
            'email_preseleccionado',
        ]);
    }

    public function validateExternalIds(array $data): void
    {
        $checks = [
            ['key' => 'trabajador_id',      'conn' => 'mysql_polifonia', 'table' => 'trabajadores', 'msg' => 'El trabajador no existe en Polifonía'],
            ['key' => 'pluton_id',          'conn' => 'mysql_pluton',    'table' => 'users',        'msg' => 'El usuario no existe en Plutón'],
            ['key' => 'user_buscador_id',   'conn' => 'mysql_buscador',  'table' => 'users',        'msg' => 'El usuario no existe en Buscador'],
            ['key' => 'worker_buscador_id', 'conn' => 'mysql_buscador',  'table' => 'workers',      'msg' => 'El trabajador no existe en Buscador'],
            ['key' => 'user_cronos_id',     'conn' => 'mysql_cronos',    'table' => 'users',        'msg' => 'El usuario no existe en Cronos'],
            ['key' => 'user_semillas_id',   'conn' => 'mysql_semillas',  'table' => 'users',        'msg' => 'El usuario no existe en Semillas'],
            ['key' => 'user_store_id',      'conn' => 'mysql_store',     'table' => 'users',        'msg' => 'El usuario no existe en Store'],
            ['key' => 'user_zeus_id',       'conn' => 'mysql_zeus',      'table' => 'users',        'msg' => 'El usuario no existe en Zeus'],
            ['key' => 'user_fichaje_id',    'conn' => 'mysql_fichajes',  'table' => 'users',        'msg' => 'El usuario no existe en Fichajes'],
        ];

        $errors = [];

        foreach ($checks as $c) {
            $id = $data[$c['key']] ?? null;
            if (empty($id)) continue;

            $exists = DB::connection($c['conn'])->table($c['table'])->where('id', $id)->exists();
            if (!$exists) $errors[$c['key']] = $c['msg'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function save(array $data): UsuarioVinculado
    {
        return UsuarioVinculado::updateOrCreate(
            ['uuid' => $data['uuid']],
            [
                'usuario_id'         => $data['usuario_id'] ?? null,
                'trabajador_id'      => $data['trabajador_id'] ?? null,
                'pluton_id'          => $data['pluton_id'] ?? null,
                'user_buscador_id'   => $data['user_buscador_id'] ?? null,
                'worker_buscador_id' => $data['worker_buscador_id'] ?? null,
                'user_cronos_id'     => $data['user_cronos_id'] ?? null,
                'user_semillas_id'   => $data['user_semillas_id'] ?? null,
                'user_store_id'      => $data['user_store_id'] ?? null,
                'user_zeus_id'       => $data['user_zeus_id'] ?? null,
                'user_fichaje_id'    => $data['user_fichaje_id'] ?? null,
            ]
        );
    }

    public function storePreseleccionados(array $payload): void
    {
        session($payload);
    }
}
