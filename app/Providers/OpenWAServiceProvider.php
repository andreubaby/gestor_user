<?php

namespace App\Providers;

use App\Services\OpenWA\OpenWAClient;
use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Support\ServiceProvider;

/**
 * OpenWA Service Provider
 *
 * Registra los servicios de OpenWA en el contenedor de inyección de dependencias
 */
class OpenWAServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios en el contenedor
     */
    public function register(): void
    {
        // Registrar OpenWAClient como singleton
        $this->app->singleton(OpenWAClient::class, function ($app) {
            return new OpenWAClient();
        });

        // Registrar WhatsappNotificationService
        $this->app->singleton(WhatsappNotificationService::class, function ($app) {
            return new WhatsappNotificationService(
                $app->make(OpenWAClient::class)
            );
        });
    }

    /**
     * Boot de los servicios
     */
    public function boot(): void
    {
        // Publicar configuración (opcional)
        $this->publishes([
            __DIR__ . '/../../config/openwa.php' => config_path('openwa.php'),
        ], 'openwa-config');

        // Registrar comandos (si los tienes)
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ValidateOpenwaConfig::class,
                \App\Console\Commands\OpenWADebugPhone::class,
                // \App\Console\Commands\RegisterOpenwaWebhook::class,
            ]);
        }
    }
}


