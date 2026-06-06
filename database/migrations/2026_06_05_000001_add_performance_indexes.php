<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migración de índices de rendimiento.
 *
 * Cuello de botella resuelto: las queries de alta frecuencia en usuarios_vinculados
 * y group_trabajador hacen full-table-scan sin estos índices.
 *
 * Impacto esperado: reducción de latencia del 60-90% en listados de trabajadores
 * y resolución de vínculos (MissingPunchReminderService, TrabajadoresIndexService, etc.).
 */
return new class extends Migration
{
    /** Índices a crear: [tabla, columna(s), nombre_índice] */
    private array $indexes = [
        // usuarios_vinculados ──────────────────────────────────────────────────
        // Usado en: MissingPunchReminderService, BienestarService, FichajesDiariosService
        ['usuarios_vinculados', ['trabajador_id'],    'idx_uv_trabajador_id'],
        ['usuarios_vinculados', ['user_fichaje_id'],  'idx_uv_user_fichaje_id'],
        ['usuarios_vinculados', ['usuario_id'],       'idx_uv_usuario_id'],

        // group_trabajador ─────────────────────────────────────────────────────
        // El UNIQUE (group_id, trabajador_id) no puede usarse para WHERE trabajador_id = ?
        ['group_trabajador',    ['trabajador_id'],    'idx_gt_trabajador_id'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $columns, $name]) {
            $this->safeAddIndex($table, $columns, $name);
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, , $name]) {
            $this->safeDropIndex($table, $name);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function safeAddIndex(string $table, array $columns, string $name): void
    {
        try {
            $cols = implode(', ', $columns);
            DB::statement("CREATE INDEX IF NOT EXISTS `{$name}` ON `{$table}` ({$cols})");
            Log::info("[Migración] Índice {$name} creado en {$table}({$cols}).");
        } catch (\Throwable $e) {
            // El índice ya existía o la BD no soporta IF NOT EXISTS → se ignora
            Log::warning("[Migración] No se pudo crear índice {$name}: " . $e->getMessage());
        }
    }

    private function safeDropIndex(string $table, string $name): void
    {
        try {
            DB::statement("DROP INDEX IF EXISTS `{$name}` ON `{$table}`");
        } catch (\Throwable $e) {
            Log::warning("[Migración] No se pudo eliminar índice {$name}: " . $e->getMessage());
        }
    }
};

