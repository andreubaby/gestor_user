<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_sequences', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('status');
            $table->unsignedBigInteger('template_source_id')->nullable()->after('is_template');

            $table->index('is_template', 'automation_sequences_is_template_idx');
            $table->foreign('template_source_id', 'automation_sequences_template_source_fk')
                ->references('id')
                ->on('automation_sequences')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('automation_sequences', function (Blueprint $table) {
            $table->dropForeign('automation_sequences_template_source_fk');
            $table->dropIndex('automation_sequences_is_template_idx');
            $table->dropColumn(['is_template', 'template_source_id']);
        });
    }
};

