<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Validar configuración de OpenWA
 *
 * Verifica que todo esté correctamente configurado para usar OpenWA
 */
class ValidateOpenwaConfig extends Command
{
    protected $signature = 'openwa:validate';

    protected $description = 'Valida la configuración de OpenWA';

    public function handle(): int
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║  OpenWA Configuration Validator                              ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $allValid = true;

        // ─── Verificar variables de entorno ─────────────────────────────────────
        $this->line('📋 <fg=cyan>Verificando variables de entorno</>');

        $checks = [
            'OPENWA_BASE_URL' => config('openwa.base_url'),
            'OPENWA_API_KEY' => config('openwa.api_key') ? '***REDACTED***' : null,
            'OPENWA_SESSION_ID' => config('openwa.session_id'),
        ];

        foreach ($checks as $key => $value) {
            if ($value) {
                $this->line("  ✅ $key: <fg=green>✓ Configurado</>");
            } else {
                $this->line("  ❌ $key: <fg=red>✗ NO CONFIGURADO</>");
                $allValid = false;
            }
        }

        $this->newLine();

        // ─── Verificar conectividad ────────────────────────────────────────────
        $this->line('🌐 <fg=cyan>Verificando conectividad con OpenWA</>');

        try {
            $client = app(\App\Services\OpenWA\OpenWAClient::class);
            $session = $client->getSession();

            $status = $session['status'] ?? $session['state'] ?? $session['status_code'] ?? 'UNKNOWN';
            $isConnected = $session['isConnected'] ?? false;

            if ($isConnected || $status === 'CONNECTED') {
                $this->line("  ✅ Conexión exitosa");
                $this->line("  📱 Estado: <fg=green>$status</>");
            } else {
                $this->line("  ⚠️  Conectado pero Estado: <fg=yellow>$status</>");
                $this->line("  💡 Escanea el código QR en la interfaz de OpenWA");
            }
        } catch (\Throwable $e) {
            $this->line("  ❌ <fg=red>Error al conectar:</> {$e->getMessage()}");
            $this->line("  💡 Verifica que:");
            $this->line("     - OpenWA está ejecutándose");
            $this->line("     - OPENWA_BASE_URL es correcto");
            $this->line("     - OPENWA_API_KEY es válida");
            $allValid = false;
        }

        $this->newLine();

        // ─── Verificar base de datos ───────────────────────────────────────────
        $this->line('💾 <fg=cyan>Verificando base de datos</>');

        try {
            $messageCount = \App\Models\WhatsappMessage::count();
            $this->line("  ✅ Tabla whatsapp_messages: <fg=green>$messageCount registros</>");
        } catch (\Throwable $e) {
            $this->line("  ❌ <fg=red>Tabla no existe</>: {$e->getMessage()}");
            $this->line("  💡 Ejecuta: php artisan migrate");
            $allValid = false;
        }

        $this->newLine();

        // ─── Verificar webhooks ────────────────────────────────────────────────
        $this->line('🪝 <fg=cyan>Verificando webhooks</>');

        $webhookSecret = config('openwa.webhook_secret');
        if ($webhookSecret) {
            $this->line("  ✅ Webhook Secret: <fg=green>Configurado</>");
        } else {
            $this->line("  ⚠️  Webhook Secret: <fg=yellow>No configurado (validación HMAC deshabilitada)</>");
        }

        $webhookUrl = route('api.webhooks.openwa');
        $this->line("  📍 Webhook URL: <fg=blue>$webhookUrl</>");

        $this->newLine();

        // ─── Verificar queue ───────────────────────────────────────────────────
        $this->line('⚙️  <fg=cyan>Verificando queue</>');

        $queueConnection = config('queue.default');
        $this->line("  📤 Queue Driver: <fg=blue>$queueConnection</>");

        if ($queueConnection === 'database' && !Schema::hasTable('jobs')) {
            $this->line("  ⚠️  Tabla 'jobs' no existe");
            $this->line("  💡 Ejecuta: php artisan queue:failed-table && php artisan migrate");
            $allValid = false;
        }

        $this->newLine();

        // ─── Verificar logs ───────────────────────────────────────────────────
        $this->line('📝 <fg=cyan>Verificando logs</>');

        $openwaLogPath = storage_path('logs/openwa.log');
        if (file_exists($openwaLogPath)) {
            $size = filesize($openwaLogPath);
            $this->line("  ✅ OpenWA Log: <fg=green>$openwaLogPath</> ($size bytes)");
        } else {
            $this->line("  ⚠️  OpenWA Log: <fg=yellow>Aún no creado</> (se creará al procesar eventos)");
        }

        $this->newLine();

        // ─── Configuración recomendada ──────────────────────────────────────
        $this->line('💡 <fg=cyan>Recomendaciones</>');

        $recommendations = [
            'Ejecutar queue worker en producción:' => 'php artisan queue:work',
            'Registrar webhook en OpenWA:' => 'php artisan openwa:register-webhook',
            'Monitorear logs:' => 'tail -f storage/logs/openwa.log',
            'Ver documentación completa:' => 'cat OPENWA_README.md',
        ];

        foreach ($recommendations as $title => $command) {
            $this->line("  • <fg=cyan>$title</>");
            $this->line("    <fg=blue>$command</>");
        }

        $this->newLine();

        // ─── Resumen final ───────────────────────────────────────────────────
        if ($allValid) {
            $this->info('╔═══════════════════════════════════════════════════════════════╗');
            $this->info('║  ✅ OpenWA está correctamente configurado                    ║');
            $this->info('╚═══════════════════════════════════════════════════════════════╝');
            return 0;
        } else {
            $this->error('╔═══════════════════════════════════════════════════════════════╗');
            $this->error('║  ❌ Hay problemas de configuración a solucionar              ║');
            $this->error('╚═══════════════════════════════════════════════════════════════╝');
            return 1;
        }
    }
}


