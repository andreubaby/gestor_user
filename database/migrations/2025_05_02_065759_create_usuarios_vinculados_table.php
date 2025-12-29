<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios_vinculados', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // UUID común
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('trabajador_id')->nullable();
            $table->unsignedBigInteger('pluton_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios_vinculados');
    }
};
