<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeguard_time_entries', function (Blueprint $table) {
            $table->string('id')->primary(); // Ej: ID-abc123-1714000000000
            $table->string('worker_id');
            $table->date('date');
            $table->unsignedInteger('hours_brutas')->default(0); // minutos
            $table->unsignedInteger('lunch_discount')->default(0); // minutos
            $table->boolean('is_lunch_manual')->default(false);
            $table->boolean('is_free_day')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('worker_id')
                  ->references('id')
                  ->on('timeguard_workers')
                  ->onDelete('cascade');

            $table->index(['worker_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeguard_time_entries');
    }
};

