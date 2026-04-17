<?php

namespace App\Console\Commands;

use App\Models\UserFichaje;
use App\Models\UserTrabajador;
use Illuminate\Console\Command;

class SyncFichajesPasswords extends Command
{
    protected $signature = 'fichajes:sync-passwords
                            {--dry-run : Muestra los cambios sin aplicarlos}
                            {--force  : No pide confirmación}';

    protected $description = 'Sincroniza las contraseñas de DB10 (trabajadores) → DB11 (fichajes) haciendo match por email';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('');
        $this->info('════════════════════════════════════════════════');
        $this->info('  Sincronización de contraseñas entre BDs');
        $this->info('  DB10 (trabajadores) ──► DB11 (fichajes)');
        $this->info('════════════════════════════════════════════════');
        if ($isDryRun) {
            $this->warn('  MODO DRY-RUN: no se aplicará ningún cambio');
        }
        $this->info('');

        // Cargar usuarios de origen (DB10)
        $this->line('📦 Cargando usuarios de DB10 (trabajadores)...');
        $source = UserTrabajador::select('email', 'password')->get()->keyBy('email');
        $this->line("   → {$source->count()} usuarios encontrados");

        // Cargar usuarios de destino (DB11)
        $this->line('📦 Cargando usuarios de DB11 (fichajes)...');
        $targets = UserFichaje::select('id', 'email', 'password')->get();
        $this->line("   → {$targets->count()} usuarios encontrados");
        $this->line('');

        $updated   = 0;
        $skipped   = 0;
        $notFound  = 0;
        $samePass  = 0;

        $rows = [];

        foreach ($targets as $target) {
            $origin = $source->get($target->email);

            if (!$origin) {
                $notFound++;
                $rows[] = ['❌', $target->email, 'No existe en DB10', '-'];
                continue;
            }

            if ($origin->password === $target->password) {
                $samePass++;
                $rows[] = ['✅', $target->email, 'Contraseña ya idéntica', '-'];
                continue;
            }

            // Hay diferencia → actualizar
            if (!$isDryRun) {
                $target->password = $origin->password;
                $target->save();
                $updated++;
                $rows[] = ['🔄', $target->email, 'Actualizada', 'OK'];
            } else {
                $skipped++;
                $rows[] = ['🔄', $target->email, 'Actualizaría', '(dry-run)'];
            }
        }

        // Tabla resumen
        $this->table(
            ['Estado', 'Email', 'Resultado', 'Acción'],
            $rows
        );

        $this->info('');
        $this->info('════════ RESUMEN ════════');
        $this->line("  🔄 Actualizados  : <fg=green>{$updated}</>");
        $this->line("  ✅ Ya correctos  : <fg=cyan>{$samePass}</>");
        $this->line("  ❌ Sin match     : <fg=red>{$notFound}</>");
        if ($isDryRun) {
            $this->line("  🔄 Actualizarían : <fg=yellow>{$skipped}</> (dry-run, no aplicado)");
        }
        $this->info('════════════════════════');
        $this->info('');

        return self::SUCCESS;
    }
}

