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

class CatalogosService
{
    public function getCatalogos(): array
    {
        return [
            'usuarios'             => Usuario::orderBy('nombre')->get(),
            'trabajadores'         => TrabajadorPolifonia::on('mysql_polifonia')->orderBy('nombre')->get(),
            'usuariosPluton'       => UserPluton::on('mysql_pluton')->orderBy('nombre')->get(),
            'usuariosBuscador'     => UserBuscador::on('mysql_buscador')->orderBy('name')->get(),
            'trabajadoresBuscador' => WorkerBuscador::on('mysql_buscador')->orderBy('name')->get(),
            'userCronos'           => UserCronos::on('mysql_cronos')->orderBy('name')->get(),
            'userSemillas'         => UserSemillas::on('mysql_semillas')->orderBy('name')->get(),
            'userStore'            => UserStore::on('mysql_store')->orderBy('name')->get(),
            'userZeus'             => UserZeus::on('mysql_zeus')->orderBy('name')->get(),
            'usuariosFichajes'     => UserFichaje::on('mysql_fichajes')->orderBy('name')->get(),
        ];
    }
}
