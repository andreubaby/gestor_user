<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_sequence_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_sequence_id');
            $table->unsignedBigInteger('scheduled_automation_id')->nullable();
            $table->unsignedSmallInteger('step_number')->nullable();
            $table->string('execution_key')->nullable()->index();
            $table->string('status'); // queued, executed, duplicate_blocked, failed, skipped
            $table->string('target_type')->nullable();
            $table->string('target_label')->nullable();
            $table->longText('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['automation_sequence_id', 'status'], 'ael_seq_status_idx');
            $table->index(['automation_sequence_id', 'happened_at'], 'ael_seq_happened_idx');

            $table->foreign('automation_sequence_id', 'ael_seq_fk')
                ->references('id')->on('automation_sequences')->cascadeOnDelete();

            $table->foreign('scheduled_automation_id', 'ael_sched_fk')
                ->references('id')->on('scheduled_automations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_sequence_execution_logs');
    }
};



