<?php

/**
 * OpenWA WhatsApp Gateway Configuration
 *
 * Configuración para integración con OpenWA API
 * https://github.com/open-wa/wa-automate-nodejs
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OpenWA Base URL
    |--------------------------------------------------------------------------
    |
    | URL base del servidor OpenWA
    |
    | En Docker:
    |  - Si OpenWA corre en otro contenedor: http://openwa:3000
    |  - Si OpenWA corre en el host (Windows): http://host.docker.internal:3000
    |
    */
    'base_url' => env('OPENWA_BASE_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Clave API para autenticar requests contra OpenWA
    | Se envía como header: X-API-Key
    |
    */
    'api_key' => env('OPENWA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Session ID
    |--------------------------------------------------------------------------
    |
    | ID de sesión en OpenWA
    | Se usa en rutas como: /api/sessions/{sessionId}/messages/send-text
    |
    */
    'session_id' => env('OPENWA_SESSION_ID') ?: 'default',

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secreto para validar HMAC de webhooks de OpenWA
    | Si está definido, validará la firma X-HMAC-SHA256
    | Null: no validar firma
    |
    */
    'webhook_secret' => env('OPENWA_WEBHOOK_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout en milisegundos para requests HTTP a OpenWA
    |
    */
    'request_timeout' => (int) env('OPENWA_REQUEST_TIMEOUT', 30000),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Loguear request/response (sin exponer secretos)
    |
    */
    'logging' => [
        'enabled' => env('APP_DEBUG', false),
        'channel' => 'openwa',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Código de país por defecto para formatear números de teléfono
    | Ej: 34 para España, 55 para México, etc.
    |
    */
    'default_country_code' => env('OPENWA_DEFAULT_COUNTRY_CODE', '34'),

    'attachment_validation' => [
        'enabled' => (bool) env('OPENWA_ATTACHMENT_VALIDATION_ENABLED', true),
        'timeout_seconds' => (int) env('OPENWA_ATTACHMENT_VALIDATION_TIMEOUT', 8),
        'max_size_bytes' => (int) env('OPENWA_ATTACHMENT_MAX_SIZE_BYTES', 52428800), // 50MB
        'allowed_mimes' => [
            'application/pdf',
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/webm',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ],
    ],
];

