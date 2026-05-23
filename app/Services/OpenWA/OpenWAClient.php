<?php

namespace App\Services\OpenWA;

use App\Exceptions\OpenWAException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para interactuar con OpenWA API
 *
 * Maneja:
 * - Envío de mensajes de texto
 * - Obtención de sesión
 * - Registro de webhooks
 * - Validación de respuestas
 */
class OpenWAClient
{
    /**
     * Base URL de OpenWA
     */
    protected string $baseUrl;

    /**
     * API Key para autenticación
     */
    protected string $apiKey;

    /**
     * Session ID
     */
    protected string $sessionId;

    /**
     * Timeout en milisegundos
     */
    protected int $timeout;

    /**
     * Habilitar logging
     */
    protected bool $loggingEnabled;

    public function __construct()
    {
        $baseUrl = config('openwa.base_url');
        $apiKey = config('openwa.api_key');

        if (!is_string($baseUrl) || trim($baseUrl) === '' || !is_string($apiKey) || trim($apiKey) === '') {
            throw new OpenWAException('OpenWA configuration missing: base_url and api_key are required');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->sessionId = (string) config('openwa.session_id', 'default');
        $this->timeout = ((int) config('openwa.request_timeout', 30000)) / 1000; // Convertir a segundos
        $this->loggingEnabled = (bool) config('openwa.logging.enabled', false);
    }

    /**
     * Enviar mensaje de texto por número de teléfono
     *
     * @param string $phone Número de teléfono (ej: "34612345678")
     * @param string $message Texto del mensaje
     * @return array Response de OpenWA
     * @throws OpenWAException
     */
    public function sendText(string $phone, string $message): array
    {
        $chatId = $this->phoneToChatId($phone);
        return $this->sendTextToChatId($chatId, $message);
    }

    /**
     * Enviar mensaje de texto por Chat ID
     *
     * @param string $chatId Chat ID en formato WhatsApp (ej: "34612345678@c.us")
     * @param string $message Texto del mensaje
     * @return array Response de OpenWA
     * @throws OpenWAException
     */
    public function sendTextToChatId(string $chatId, string $message): array
    {
        $payload = [
            'chatId' => $chatId,
            'text' => $message,
        ];

        try {
            return $this->requestToSessionEndpoint(
                operation: 'sendTextToChatId',
                method: 'POST',
                path: '/messages/send-text',
                payload: $payload,
                asJson: true
            );
        } catch (\Exception $e) {
            throw new OpenWAException(
                "Failed to send message to {$chatId}: " . $e->getMessage(),
                null,
                0,
                $e
            );
        }
    }

    /**
     * Enviar archivo por Chat ID (documento/video/etc) usando URL pública.
     *
     * @param string $chatId
     * @param string $fileUrl URL pública del archivo
     * @param string|null $caption Texto/caption opcional
     * @param string|null $filename Nombre de archivo opcional
     */
    public function sendFileToChatId(string $chatId, string $fileUrl, ?string $caption = null, ?string $filename = null): array
    {
        $basePayload = [
            'chatId' => $chatId,
        ];

        if ($caption !== null && trim($caption) !== '') {
            $basePayload['caption'] = $caption;
        }

        if ($filename !== null && trim($filename) !== '') {
            $basePayload['filename'] = $filename;
        }

        $candidates = [
            [
                'path' => '/messages/send-file',
                'payload' => array_merge($basePayload, ['file' => $fileUrl]),
            ],
            [
                'path' => '/messages/send-media',
                'payload' => array_merge($basePayload, ['media' => $fileUrl]),
            ],
            [
                'path' => '/messages/send-document',
                'payload' => array_merge($basePayload, ['url' => $fileUrl]),
            ],
        ];

        $lastException = null;

        foreach ($candidates as $candidate) {
            try {
                return $this->requestToSessionEndpoint(
                    operation: 'sendFileToChatId',
                    method: 'POST',
                    path: $candidate['path'],
                    payload: $candidate['payload'],
                    asJson: true
                );
            } catch (\Exception $e) {
                $lastException = $e;
                // Probamos siguiente variante para máxima compatibilidad con gateways OpenWA.
                continue;
            }
        }

        throw new OpenWAException(
            "Failed to send file to {$chatId}: " . ($lastException?->getMessage() ?? 'unknown error'),
            null,
            0,
            $lastException
        );
    }

    /**
     * Enviar archivo por número de teléfono.
     */
    public function sendFile(string $phone, string $fileUrl, ?string $caption = null, ?string $filename = null): array
    {
        $chatId = $this->phoneToChatId($phone);
        return $this->sendFileToChatId($chatId, $fileUrl, $caption, $filename);
    }

    /**
     * Obtener estado de la sesión
     *
     * @return array Session data
     * @throws OpenWAException
     */
    public function getSession(): array
    {
        try {
            return $this->requestToSessionEndpoint(
                operation: 'getSession',
                method: 'GET',
                path: ''
            );
        } catch (\Exception $e) {
            throw new OpenWAException(
                "Failed to get session: " . $e->getMessage(),
                null,
                0,
                $e
            );
        }
    }

    /**
     * Registrar webhook para recibir eventos
     *
     * @param string $url URL que recibirá los eventos
     * @param array $events Eventos a escuchar (ej: ['message.received', 'session.status'])
     * @param string|null $secret Secreto para HMAC (opcional)
     * @return array Response de OpenWA
     * @throws OpenWAException
     */
    public function registerWebhook(string $url, array $events, ?string $secret = null): array
    {
        $payload = [
            'url' => $url,
            'events' => $events,
        ];

        if ($secret) {
            $payload['secret'] = $secret;
        }

        try {
            return $this->requestToSessionEndpoint(
                operation: 'registerWebhook',
                method: 'POST',
                path: '/webhooks/register',
                payload: $payload,
                asJson: true,
                excludeSecretsFromLog: true
            );
        } catch (\Exception $e) {
            throw new OpenWAException(
                "Failed to register webhook: " . $e->getMessage(),
                null,
                0,
                $e
            );
        }
    }

    /**
     * Obtener grupos de WhatsApp de la sesión activa
     *
     * @return array<int, array<string, mixed>>
     * @throws OpenWAException
     */
    public function getSessionGroups(): array
    {
        try {
            $response = $this->requestToSessionEndpoint(
                operation: 'getSessionGroups',
                method: 'GET',
                path: '/groups'
            );

            if (!is_array($response)) {
                return [];
            }

            if (array_key_exists('value', $response) && is_array($response['value'])) {
                return $response['value'];
            }

            return array_is_list($response) ? $response : [];
        } catch (\Exception $e) {
            throw new OpenWAException(
                "Failed to get session groups: " . $e->getMessage(),
                null,
                0,
                $e
            );
        }
    }

    /**
     * Convertir número de teléfono a Chat ID WhatsApp
     *
     * Formato: {countrycode}{number}@c.us
     * Ej: "34612345678" -> "34612345678@c.us"
     *
     * @param string $phone Número de teléfono
     * @return string Chat ID
     */
    protected function phoneToChatId(string $phone): string
    {
        // Remover caracteres especiales
        $phone = preg_replace('/[^\d]/', '', $phone);

        // Si el número comienza con 0, removerlo (ej: 0612345678 -> 612345678)
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Si no tiene código de país, añadirlo
        if (strlen($phone) <= 9) {
            $countryCode = config('openwa.default_country_code', '34');
            $phone = $countryCode . $phone;
        }

        return "{$phone}@c.us";
    }

    /**
     * Ejecuta una request en un endpoint de sesión.
     * Si OpenWA devuelve 404 para la sesión configurada, intenta resolver una sesión válida y reintenta una vez.
     *
     * @throws OpenWAException
     */
    protected function requestToSessionEndpoint(
        string $operation,
        string $method,
        string $path,
        ?array $payload = null,
        bool $asJson = false,
        bool $excludeSecretsFromLog = false
    ): array {
        $response = $this->sendSessionRequest(
            method: $method,
            path: $path,
            payload: $payload,
            asJson: $asJson,
            excludeSecretsFromLog: $excludeSecretsFromLog
        );

        if ($response->status() === 404) {
            $resolvedSessionId = $this->resolveActiveSessionId();

            if ($resolvedSessionId !== null && $resolvedSessionId !== $this->sessionId) {
                $previousSessionId = $this->sessionId;
                $this->sessionId = $resolvedSessionId;

                Log::channel(config('openwa.logging.channel', 'stack'))->warning('OpenWA session auto-recovered after 404', [
                    'previous_session_id' => $previousSessionId,
                    'resolved_session_id' => $resolvedSessionId,
                    'operation' => $operation,
                ]);

                $response = $this->sendSessionRequest(
                    method: $method,
                    path: $path,
                    payload: $payload,
                    asJson: $asJson,
                    excludeSecretsFromLog: $excludeSecretsFromLog
                );
            }
        }

        return $this->handleResponse($response, $operation);
    }

    protected function sendSessionRequest(
        string $method,
        string $path,
        ?array $payload = null,
        bool $asJson = false,
        bool $excludeSecretsFromLog = false
    ): Response {
        $endpoint = "/api/sessions/{$this->sessionId}{$path}";
        $this->logRequest($method, $endpoint, $payload, $excludeSecretsFromLog);

        $request = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->timeout($this->timeout);

        if ($asJson) {
            $request = $request
                ->asJson()
                ->withHeaders(['Content-Type' => 'application/json']);
        }

        $url = "{$this->baseUrl}{$endpoint}";

        if ($method === 'GET') {
            return $request->get($url);
        }

        if ($method === 'POST') {
            return $request->post($url, $payload ?? []);
        }

        throw new OpenWAException("Unsupported HTTP method {$method} for OpenWA request");
    }

    /**
     * Resuelve una sesión disponible consultando /api/sessions.
     * Prioriza sesiones conectadas y, si no hay, devuelve la primera válida.
     */
    protected function resolveActiveSessionId(): ?string
    {
        $endpoint = '/api/sessions';
        $this->logRequest('GET', $endpoint);

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}{$endpoint}");
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $sessions = $this->extractSessionsFromListResponse($response->json());

        if (empty($sessions)) {
            return null;
        }

        foreach ($sessions as $session) {
            $status = strtoupper((string) ($session['status'] ?? $session['state'] ?? $session['status_code'] ?? ''));
            $isConnected = (bool) ($session['isConnected'] ?? $session['connected'] ?? false);

            if ($isConnected || in_array($status, ['CONNECTED', 'READY', 'WORKING', 'AUTHENTICATED'], true)) {
                return (string) $session['session_id'];
            }
        }

        return (string) $sessions[0]['session_id'];
    }

    /**
     * Normaliza formatos posibles de /api/sessions a una lista homogénea.
     *
     * @param mixed $data
     * @return array<int, array<string, mixed>>
     */
    protected function extractSessionsFromListResponse(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $source = $data;

        if (isset($data['sessions']) && is_array($data['sessions'])) {
            $source = $data['sessions'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $source = $data['data'];
        }

        $normalized = [];

        foreach ($source as $key => $session) {
            if (is_string($session)) {
                $normalized[] = ['session_id' => $session];
                continue;
            }

            if (!is_array($session)) {
                continue;
            }

            $sessionId = $session['session_id']
                ?? $session['sessionId']
                ?? $session['id']
                ?? $session['name']
                ?? (is_string($key) ? $key : null);

            if (!is_string($sessionId) || $sessionId === '') {
                continue;
            }

            $session['session_id'] = $sessionId;
            $normalized[] = $session;
        }

        return $normalized;
    }

    /**
     * Manejar response HTTP
     *
     * @param Response $response
     * @param string $operation Nombre de la operación
     * @return array
     * @throws OpenWAException
     */
    protected function handleResponse(
        Response $response,
        string $operation
    ): array {
        $data = $response->json();

        $this->logResponse($operation, $response->status(), $data);

        if (!$response->successful()) {
            throw new OpenWAException(
                "OpenWA {$operation} failed with status {$response->status()}",
                $data,
                $response->status()
            );
        }

        if (isset($data['error']) || (isset($data['status']) && $data['status'] === 'error')) {
            throw new OpenWAException(
                "OpenWA {$operation} error: " . ($data['message'] ?? 'Unknown error'),
                $data
            );
        }

        return $data ?? [];
    }

    /**
     * Loguear request
     */
    protected function logRequest(string $method, string $endpoint, ?array $payload = null, bool $excludeSecrets = false): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $logPayload = $payload;

        if ($excludeSecrets && $logPayload && isset($logPayload['secret'])) {
            $logPayload['secret'] = '***REDACTED***';
        }

        Log::channel(config('openwa.logging.channel', 'stack'))->debug(
            "OpenWA Request: {$method} {$endpoint}",
            ['payload' => $logPayload]
        );
    }

    /**
     * Loguear response
     */
    protected function logResponse(
        string $operation,
        int $status,
        ?array $data
    ): void {
        if (!$this->loggingEnabled) {
            return;
        }

        Log::channel(config('openwa.logging.channel', 'stack'))->debug(
            "OpenWA Response: {$operation}",
            [
                'status' => $status,
                'data' => $data,
            ]
        );
    }
}




