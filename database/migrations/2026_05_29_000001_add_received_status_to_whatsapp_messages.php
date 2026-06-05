<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE whatsapp_messages MODIFY status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'received') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE whatsapp_messages MODIFY status ENUM('pending', 'sent', 'delivered', 'read', 'failed') NOT NULL DEFAULT 'pending'");
    }
};