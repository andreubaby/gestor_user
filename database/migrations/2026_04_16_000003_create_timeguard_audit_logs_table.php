<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeguard_audit_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('entry_id');
            $table->bigInteger('timestamp'); // Unix ms (como en la app TS)
            $table->string('field');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('user')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('entry_id')
                  ->references('id')
                  ->on('timeguard_time_entries')
                  ->onDelete('cascade');

            $table->index('entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeguard_audit_logs');
    }
};

