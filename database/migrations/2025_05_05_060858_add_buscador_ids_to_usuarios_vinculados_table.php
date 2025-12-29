<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('usuarios_vinculados', function (Blueprint $table) {
            $table->unsignedBigInteger('user_buscador_id')->nullable()->after('pluton_id');
            $table->unsignedBigInteger('worker_buscador_id')->nullable()->after('user_buscador_id');
            $table->unsignedBigInteger('user_cronos_id')->nullable()->after('worker_buscador_id');
            $table->unsignedBigInteger('user_semillas_id')->nullable()->after('user_buscador_id');
            $table->unsignedBigInteger('user_store_id')->nullable()->after('user_cronos_id');
            $table->unsignedBigInteger('user_zeus_id')->nullable()->after('user_store_id');
        });
    }

    public function down()
    {
        Schema::table('usuarios_vinculados', function (Blueprint $table) {
            $table->dropColumn(['user_buscador_id', 'worker_buscador_id', 'user_cronos_id', 'user_semillas_id','user_store_id', 'user_zeus_id']);
        });
    }
};
