<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_trabajador', function (Blueprint $table) {
            $table->id();

            // FK válida (misma BD)
            $table->foreignId('group_id')
                ->constrained('groups')
                ->cascadeOnDelete();

            // 👇 trabajador está en OTRA BD → sin FK
            $table->unsignedBigInteger('trabajador_id');

            // opcional: rol dentro del grupo
            $table->string('role')->nullable();

            $table->timestamps();

            // Evita duplicados
            $table->unique(['group_id', 'trabajador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_trabajador');
    }
};
