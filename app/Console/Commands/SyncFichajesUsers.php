<?php

namespace App\Console\Commands;

use App\Models\UserFichaje;
use App\Models\UserTrabajador;
use App\Models\Trabajador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncFichajesUsers extends Command
{
    protected $signature = 'fichajes:sync-users
                            {--dry-run  : Muestra los cambios sin aplicarlos}
                            {--revert=  : Revierte usando el backup indicado (fichero en storage/app/backups/)}
                            {--list-backups : Lista los backups disponibles para revertir}';

    protected $description = 'Sincroniza usuarios de DB10 (trabajadores) → DB11 (fichajes) por email. Genera backup antes de aplicar.';

    private string $backupDir = 'backups/fichajes_users';

    public function handle(): int
    {
        if ($this->option('list-backups')) {
            return $this->listBackups();
        }

        if ($revertFile = $this->option('revert')) {
            return $this->revert($revertFile);
        }

        return $this->sync();
    }

    // ─── SINCRONIZACIÓN ──────────────────────────────────────────────────────

    private function sync(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('');
        $this->info('════════════════════════════════════════════════════');
        $this->info('  Sincronización de usuarios entre BDs');
        $this->info('  DB10 (trabajadores) ──► DB11 (fichajes)');
        $this->info('════════════════════════════════════════════════════');
        if ($isDryRun) {
            $this->warn('  MODO DRY-RUN: no se aplicará ningún cambio');
        }
        $this->info('');

        // Cargar destino (DB11)
        $this->line('📦 Cargando usuarios de DB11 (fichajes)...');
        $source = UserTrabajador::select('name', 'email', 'password')->get()->keyBy(fn($u) => strtolower($u->email));
        $this->line("   → {$source->count()} usuarios encontrados");

        // Cargar emails activos de DB3 (polifonía → trabajadores con activo = 1)
        $this->line('🔍 Cargando trabajadores activos de DB3 (polifonía)...');
        $activosDB3 = Trabajador::where('activo', 1)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->map(fn($e) => strtolower($e))
            ->flip(); // convierte en lookup O(1)
        $this->line("   → {$activosDB3->count()} trabajadores activos encontrados");
        $this->line('');
        $this->line('📦 Cargando usuarios de DB11 (fichajes)...');
        $targets = UserFichaje::select('id', 'name', 'email', 'password', 'google_id')->get();
        $this->line("   → {$targets->count()} usuarios encontrados");
        $this->line('');

        // Generar backup ANTES de cualquier cambio
        $backupFile = null;
        if (!$isDryRun) {
            $backupFile = $this->createBackup($targets->toArray());
            $this->info("💾 Backup creado: <fg=cyan>{$backupFile}</>");
            $this->line('');
        }

        $created  = 0;
        $updated  = 0;
        $skipped  = 0;
        $noSource = 0;
        $rows     = [];

        $targetsByEmail = $targets->keyBy(fn($u) => strtolower($u->email));

        // Solo procesar usuarios de DB10 que NO existen en DB11 Y están activos en DB3
        foreach ($source as $emailKey => $origin) {
            if ($targetsByEmail->has($emailKey)) {
                // Ya existe en DB11 → ignorar completamente
                continue;
            }

            // Verificar que el trabajador está activo en DB3
            if (!$activosDB3->has($emailKey)) {
                $rows[] = ['⏭️', $origin->email, 'Omitido (inactivo en DB3)', $origin->name];
                continue;
            }

            // No existe en DB11 → crear
            // DB10 usa latin1 → convertir a UTF-8 antes de insertar en DB11 (utf8mb4)
            $nameUtf8 = mb_convert_encoding($origin->name, 'UTF-8', 'ISO-8859-1');
            if (!$isDryRun) {
                UserFichaje::create([
                    'name'     => $nameUtf8,
                    'email'    => $origin->email,
                    'password' => $origin->password,
                ]);
                $created++;
            } else {
                $skipped++;
            }
            $rows[] = ['➕', $origin->email, $isDryRun ? 'Crearía' : 'Creado', $nameUtf8];
        }

        $this->table(['Estado', 'Email', 'Resultado', 'Nombre'], $rows);

        $this->info('');
        $this->info('════════ RESUMEN ════════');
        if ($isDryRun) {
            $this->line("  ➕ Crearían : <fg=green>{$skipped}</> usuarios nuevos (dry-run, no aplicado)");
        } else {
            $this->line("  ➕ Creados  : <fg=green>{$created}</> usuarios nuevos en DB11");
        }
        if ($backupFile) {
            $this->info('');
            $this->info("💡 Para revertir: php artisan fichajes:sync-users --revert={$backupFile}");
        }
        $this->info('════════════════════════');
        $this->info('');

        return self::SUCCESS;
    }

    // ─── BACKUP ───────────────────────────────────────────────────────────────

    private function createBackup(array $users): string
    {
        $filename = $this->backupDir . '/backup_' . now()->format('Y-m-d_H-i-s') . '.json';
        Storage::put($filename, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $filename;
    }

    // ─── REVERT ───────────────────────────────────────────────────────────────

    private function revert(string $file): int
    {
        if (!Storage::exists($file)) {
            $this->error("❌ Backup no encontrado: {$file}");
            $this->line('   Usa --list-backups para ver los disponibles.');
            return self::FAILURE;
        }

        $data = json_decode(Storage::get($file), true);

        if (!$this->confirm("⚠️  Esto reemplazará los datos actuales de DB11 con el backup '{$file}'. ¿Continuar?")) {
            $this->warn('Operación cancelada.');
            return self::SUCCESS;
        }

        // Backup del estado actual antes de revertir
        $currentUsers = UserFichaje::select('id', 'name', 'email', 'password', 'google_id')->get()->toArray();
        $preRevertBackup = $this->createBackup($currentUsers);
        $this->info("💾 Backup del estado actual guardado en: {$preRevertBackup}");

        $restored = 0;
        foreach ($data as $user) {
            $target = UserFichaje::find($user['id']);
            if ($target) {
                $target->name     = $user['name'];
                $target->email    = $user['email'];
                $target->password = $user['password'];
                $target->save();
                $restored++;
            }
        }

        $this->info('');
        $this->info("✅ Reversión completada: {$restored} usuarios restaurados.");
        $this->info('');

        return self::SUCCESS;
    }

    // ─── LISTAR BACKUPS ───────────────────────────────────────────────────────

    private function listBackups(): int
    {
        $files = Storage::files($this->backupDir);

        if (empty($files)) {
            $this->warn('No hay backups disponibles.');
            return self::SUCCESS;
        }

        $rows = array_map(fn($f) => [
            $f,
            Storage::size($f) . ' bytes',
            Storage::lastModified($f) ? date('Y-m-d H:i:s', Storage::lastModified($f)) : '-',
        ], $files);

        $this->table(['Archivo', 'Tamaño', 'Fecha'], $rows);
        $this->line('');
        $this->line('Uso: php artisan fichajes:sync-users --revert=<archivo>');

        return self::SUCCESS;
    }
}




