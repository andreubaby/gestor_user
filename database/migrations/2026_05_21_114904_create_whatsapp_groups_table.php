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
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('chat_id')->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('member_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('chat_id');
        });

        Schema::create('whatsapp_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('trabajador_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('chat_id');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('whatsapp_groups')->onDelete('cascade');
            $table->unique(['group_id', 'chat_id']);
            $table->index(['group_id', 'chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_groups');
    }
};
