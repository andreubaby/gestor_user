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
        Schema::create('scheduled_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_sequence_id')->constrained('automation_sequences')->onDelete('cascade');
            $table->time('scheduled_time'); // Hora del día a ejecutar
            $table->json('days_of_week')->nullable(); // 1=Lunes, 0=Domingo
            $table->string('status')->default('active'); // active, inactive
            $table->dateTime('last_executed_at')->nullable();
            $table->dateTime('next_execution_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_automations');
    }
};

