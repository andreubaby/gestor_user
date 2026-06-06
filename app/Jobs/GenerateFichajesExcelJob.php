<?php

namespace App\Jobs;

use App\Services\FichajesDiariosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Job para generar exportaciones Excel de fichajes en background.
 *
 * ⚡ Problema que resuelve:
 *   La generación del Excel puede tardar 10-60 segundos para muchos trabajadores
 *   y meses de datos. Ejecutarlo en el request HTTP provoca timeouts y bloquea workers.
 *
 * Uso desde controlador:
 *   GenerateFichajesExcelJob::dispatch($request->all(), auth()->id(), $request->user()?->email)
 *       ->onQueue('exports');
 *
 * El archivo se guarda en storage/app/exports/ y se notifica por log (o email si se configura).
 */
class GenerateFichajesExcelJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Número de intentos antes de marcar como fallido. */
    public int $tries = 2;

    /** Tiempo máximo de ejecución en segundos (5 minutos). */
    public int $timeout = 300;

    /**
     * @param array<string,mixed> $params       Parámetros del request (month, grupo, estado, trabajador_id)
     * @param int|null            $userId        ID del usuario que solicitó la exportación
     * @param string|null         $notifyEmail   Email donde notificar cuando el archivo esté listo
     */
    public function __construct(
        private readonly array   $params,
        private readonly ?int    $userId      = null,
        private readonly ?string $notifyEmail = null,
    ) {}

    /**
     * Genera el Excel en background y lo mueve a almacenamiento persistente.
     */
    public function handle(FichajesDiariosService $service): void
    {
        Log::info('[GenerateFichajesExcelJob] Iniciando generación de Excel', [
            'params'  => $this->params,
            'user_id' => $this->userId,
        ]);

        // 1) Generar el Excel (devuelve [$tmpPath, $fileName])
        [$tmpPath, $fileName] = $service->generateExcelFile($this->params);

        // 2) Mover a almacenamiento persistente en exports/
        $exportDir  = storage_path('app/exports');
        if (!is_dir($exportDir) && !mkdir($exportDir, 0755, true) && !is_dir($exportDir)) {
            throw new \RuntimeException("No se pudo crear el directorio de exportaciones: {$exportDir}");
        }

        $finalPath = $exportDir . DIRECTORY_SEPARATOR . $fileName;
        rename($tmpPath, $finalPath);

        Log::info('[GenerateFichajesExcelJob] Excel generado', [
            'file'    => $finalPath,
            'user_id' => $this->userId,
        ]);

        // 3) Notificar (por email si se configuró)
        if ($this->notifyEmail) {
            $this->notifyByEmail($fileName, $finalPath);
        }
    }

    /**
     * Se ejecuta si el Job falla todos los intentos.
     */
    public function failed(?\Throwable $exception = null): void
    {
        Log::error('[GenerateFichajesExcelJob] Falló la generación del Excel', [
            'params'    => $this->params,
            'user_id'   => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function notifyByEmail(string $fileName, string $filePath): void
    {
        if (!$this->notifyEmail) return;

        try {
            // Envío simple con Mail::raw; si tienes un Mailable dedicado, úsalo aquí.
            Mail::raw(
                "Tu exportación de fichajes está lista:\n\n  {$fileName}\n\n"
                . "Descárgala desde el panel de administración.",
                function ($m) use ($fileName) {
                    $m->to($this->notifyEmail)
                      ->subject("✅ Exportación lista: {$fileName}");
                }
            );

            Log::info('[GenerateFichajesExcelJob] Notificación enviada', [
                'email' => $this->notifyEmail,
                'file'  => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[GenerateFichajesExcelJob] No se pudo enviar notificación', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


