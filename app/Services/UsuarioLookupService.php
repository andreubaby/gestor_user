<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\TrabajadorPolifonia;
use App\Models\UserPluton;
use App\Models\UserBuscador;
use App\Models\WorkerBuscador;
use App\Models\UserCronos;
use App\Models\UserSemillas;
use App\Models\UserStore;
use App\Models\UserZeus;
use App\Models\UserFichaje;
use App\Models\UsuarioVinculado;
use Illuminate\Support\Str;

class UsuarioLookupService
{
    public function resolveByIdentificador(string $identificador): array
    {
        $usuario = $trabajador = $pluton = null;

        if (Str::isUuid($identificador)) {
            $vinculo = UsuarioVinculado::where('uuid', $identificador)->firstOrFail();

            $usuario    = Usuario::find($vinculo->usuario_id);
            $trabajador = TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id);
            $pluton     = UserPluton::on('mysql_pluton')->find($vinculo->pluton_id);

            $email = $usuario?->email ?? $trabajador?->email ?? $pluton?->email;
        } else {
            $email = mb_strtolower(trim($identificador));
            $usuario    = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
            $trabajador = TrabajadorPolifonia::whereRaw('LOWER(email) = ?', [$email])->first();
            $pluton     = UserPluton::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        $extras = $this->findExtrasByEmail($email);

        $userFichaje = $this->findFichajeByEmail($email);

        return [
            'email' => $email,
            'usuario' => $usuario,
            'trabajador' => $trabajador,
            'usuarioPluton' => $pluton,

            'usuarioBuscador' => $extras['usuarioBuscador'],
            'trabajadorBuscador' => $extras['trabajadorBuscador'],
            'usuarioCronos' => $extras['usuarioCronos'],
            'usuarioSemillas' => $extras['usuarioSemillas'],
            'usuarioStore' => $extras['usuarioStore'],
            'usuarioZeus' => $extras['usuarioZeus'],

            'userFichaje' => $userFichaje,
        ];
    }

    public function findVinculoFor(array $models): ?UsuarioVinculado
    {
        $usuario = $models['usuario'] ?? null;
        $trabajador = $models['trabajador'] ?? null;
        $pluton = $models['usuarioPluton'] ?? null;

        return UsuarioVinculado::where(function ($q) use ($usuario, $trabajador, $pluton) {
            if ($usuario)    $q->orWhere('usuario_id', $usuario->id);
            if ($trabajador) $q->orWhere('trabajador_id', $trabajador->id);
            if ($pluton)     $q->orWhere('pluton_id', $pluton->id);
        })->first();
    }

    private function findExtrasByEmail(?string $email): array
    {
        $usuarioBuscador = $trabajadorBuscador = null;
        $usuarioCronos = $usuarioSemillas = null;
        $usuarioStore = $usuarioZeus = null;

        if (!empty($email)) {
            $usuarioBuscador    = UserBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
            $trabajadorBuscador = WorkerBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioCronos      = UserCronos::on('mysql_cronos')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioSemillas    = UserSemillas::on('mysql_semillas')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioStore       = UserStore::on('mysql_store')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioZeus        = UserZeus::on('mysql_zeus')->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        return compact(
            'usuarioBuscador','trabajadorBuscador',
            'usuarioCronos','usuarioSemillas','usuarioStore','usuarioZeus'
        );
    }

    private function findFichajeByEmail(?string $email): ?UserFichaje
    {
        if (empty($email)) return null;
        $emailKey = mb_strtolower(trim((string)$email));
        if ($emailKey === '') return null;

        return UserFichaje::on('mysql_fichajes')
            ->whereRaw('LOWER(email) = ?', [$emailKey])
            ->first();
    }
}
