<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioVinculado extends Model
{
    protected $table = 'usuarios_vinculados';

    // Agrega los nuevos campos aquí
    protected $fillable = [
        'uuid',
        'usuario_id',
        'trabajador_id',
        'pluton_id',
        'user_buscador_id',
        'worker_buscador_id',
        'user_cronos_id',
        'user_semillas_id',
        'user_store_id',
        'user_zeus_id'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function trabajador()
    {
        return $this->belongsTo(TrabajadorPolifonia::class, 'trabajador_id', 'id');
    }

    public function pluton()
    {
        return $this->belongsTo(UserPluton::class, 'pluton_id', 'id');
    }

    public function cronos()
    {
        return $this->belongsTo(UserCronos::class, 'user_cronos_id', 'id');
    }

    public function semillas()
    {
        return $this->belongsTo(UserSemillas::class, 'user_semillas_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(UserStore::class, 'user_store_id', 'id');
    }

    public function zeus()
    {
        return $this->belongsTo(UserZeus::class, 'user_zeus_id', 'id');
    }

    public function userBuscador()
    {
        return $this->belongsTo(UserBuscador::class, 'user_buscador_id', 'id')
            ->setConnection('mysql_buscador');
    }

    public function workerBuscador()
    {
        return $this->belongsTo(WorkerBuscador::class, 'worker_buscador_id', 'id')
            ->setConnection('mysql_buscador');
    }
}
