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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->default('default'); // ID de sesión OpenWA
            $table->string('chat_id'); // Formato WhatsApp: {country}{number}@c.us
            $table->enum('direction', ['inbound', 'outbound']); // Dirección del mensaje
            $table->string('message_id')->nullable()->unique(); // ID único del mensaje en OpenWA
            $table->longText('text'); // Contenido del mensaje
            $table->json('payload')->nullable(); // Payload completo de OpenWA
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('error_message')->nullable(); // Mensaje de error si falló
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('chat_id');
            $table->index('session_id');
            $table->index('direction');
            $table->index('status');
            $table->index('created_at');
            $table->index(['chat_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};

