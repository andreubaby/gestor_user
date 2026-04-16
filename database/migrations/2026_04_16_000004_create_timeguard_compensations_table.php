<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeguard_compensations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('worker_id');
            $table->date('date');
            $table->enum('type', ['PAYMENT', 'REST_HOURS', 'FREE_DAY']);
            $table->unsignedInteger('minutes'); // minutos compensados
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
        Schema::dropIfExists('timeguard_compensations');
    }
};

