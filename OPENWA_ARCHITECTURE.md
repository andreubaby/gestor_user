# 🏗️ Arquitectura de Integración OpenWA

## Flujo de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                         TU APLICACIÓN LARAVEL                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                    CONTROLADOR / EVENTO                      │   │
│  │  OrderController, UserController, AuthController, etc.      │   │
│  └──────────────┬───────────────────────────────────────────────┘   │
│                 │                                                    │
│                 ▼                                                    │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │   SERVICIO NOTIFICACIONES (WhatsappNotificationService)    │   │
│  │  - sendWelcomeMessage()                                     │   │
│  │  - sendOtp()                                                │   │
│  │  - sendOrderUpdate()                                        │   │
│  │  - sendToUser()                                             │   │
│  │  - sendToPhone()                                            │   │
│  └──────────────┬───────────────────────────────────────────────┘   │
│                 │                                                    │
│         ┌───────┴────────┐                                           │
│         │                │                                           │
│         ▼ (async)        ▼ (sync)                                    │
│  ┌─────────────┐    ┌──────────────┐                                │
│  │ Job Queue   │    │ Client HTTP  │                                │
│  └────┬────────┘    └──────┬───────┘                                │
│       │                    │                                         │
│       ▼                    ▼                                         │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │          OpenWAClient (app/Services/OpenWA/)                │   │
│  │  - sendText(phone, message)                                 │   │
│  │  - sendTextToChatId(chatId, message)                        │   │
│  │  - getSession()                                             │   │
│  │  - registerWebhook(url, events)                             │   │
│  │  - Conversión automática: teléfono → Chat ID                │   │
│  │  - Logging sin exponer secretos                             │   │
│  └────────┬──────────────────────────────────────────────────┬──┘   │
│           │ HTTP Client (Illuminate\Support\Facades\Http)   │       │
│           │ Headers: X-API-Key: {api_key}                  │       │
│           │ Timeout: configurable                          │       │
│           │ Retry: 3 intentos                              │       │
│           │                                                │       │
└───────────┼────────────────────────────────────────────────┼───────┘
            │                                                │
            │ API REST                                       │ Webhook
            │ POST /api/sessions/{sessionId}/messages/...   │ POST /api/webhooks/openwa
            │                                                │
            ▼                                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      OPENWA GATEWAY                                  │
│  - Expone API REST en http://openwa:3000 (o host.docker.internal) │
│  - Gestiona sesiones de WhatsApp                                    │
│  - Recibe eventos (mensajes, cambios de estado)                     │
│  - Envía/recibe mensajes de WhatsApp                                │
└─────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    WHATSAPP (Red Meta)                              │
│  - Servidor de WhatsApp                                             │
│  - Usuarios reales                                                  │
│  - Mensajes P2P                                                     │
└─────────────────────────────────────────────────────────────────────┘


Webhook Flow (Eventos desde OpenWA):

OpenWA                              Laravel                         BD
  │                                  │
  ├─ message.received ─────────────► WebhookController.handle()
  │                                  │
  │                                  ├─► Validar HMAC
  │                                  │
  │                                  ├─► Verificar idempotencia
  │                                  │
  │                                  ├─► handleMessageReceived()
  │                                  │    │
  │                                  │    └─► WhatsappMessage::create()
  │                                  │         │
  │                                  │         ▼
  │                                  │      ┌────────────────────┐
  │                                  │      │ whatsapp_messages  │
  │                                  │      │ direction: inbound │
  │                                  │      │ status: received   │
  │                                  │      └────────────────────┘
  │                                  │
  ├─ message.status ──────────────► handleMessageStatus()
  │                                  │
  │                                  └─► $message->markAsDelivered()
  │                                       $message->markAsRead()
  │
  ├─ session.status ──────────────► handleSessionStatus()
  │                                  │ (Logging)
  │
  ├─ session.qr ──────────────────► handleSessionQr()
  │                                  │ (QR para conectar WhatsApp)
  │
  └─ session.disconnected ────────► handleSessionDisconnected()
                                     │ (Sesión desconectada)
```

## Estructura de Directorios

```
app/
├── Services/
│   ├── OpenWA/
│   │   └── OpenWAClient.php ............. Cliente HTTP para OpenWA
│   └── WhatsApp/
│       └── WhatsappNotificationService.php  Servicio de notificaciones
│
├── Jobs/
│   └── SendWhatsappMessageJob.php ....... Job para envío asincrónico
│
├── Http/
│   └── Controllers/
│       └── OpenWAWebhookController.php .. Webhook handler
│
├── Models/
│   ├── WhatsappMessage.php .............. Modelo de persistencia
│   └── User.php (actualizado)
│
├── Exceptions/
│   └── OpenWAException.php .............. Excepción personalizada
│
├── Console/
│   └── Commands/
│       └── ValidateOpenwaConfig.php .... Comando de validación
│
└── Providers/
    └── OpenWAServiceProvider.php ........ Service Provider

database/
├── migrations/
│   ├── 2026_05_21_000001_create_whatsapp_messages_table.php
│   └── 2026_05_21_000002_add_phone_to_users_table.php

config/
├── openwa.php ........................... Configuración OpenWA
└── logging.php (actualizado)

routes/
└── api.php (actualizado) ................ POST /api/webhooks/openwa

tests/
├── Unit/
│   ├── Services/OpenWA/OpenWAClientTest.php
│   └── Jobs/SendWhatsappMessageJobTest.php
├── Feature/
│   ├── Http/Controllers/OpenWAWebhookControllerTest.php
│   └── OpenWAIntegrationTest.php
```

## Flujo de Envío de Mensaje

### Opción 1: Síncrono (Bloquea la respuesta)

```
User Request
    │
    ▼
Controller
    │
    ├─► app(WhatsappNotificationService::class)
    │   ->sendToUser($user, $message, async: false)
    │
    └─► OpenWAClient::sendTextToChatId()
        │
        ├─► HTTP Request a OpenWA
        │
        └─► Espera respuesta
            │
            ├─► ¿Éxito? → return ['id' => '...', 'status' => 'sent']
            │
            └─► ¿Error? → throw OpenWAException

Result
    │
    ├─► Respuesta enviada al cliente
    │
    └─► Mensaje en BD (si se guardó antes)
```

### Opción 2: Asincrónico (Encolado)

```
User Request
    │
    ▼
Controller
    │
    ├─► app(WhatsappNotificationService::class)
    │   ->sendToUser($user, $message, async: true)  [Default]
    │
    └─► SendWhatsappMessageJob::dispatch()
        │
        ├─► Encolado en Redis/DB
        │
        └─► Respuesta inmediata al cliente

[En paralelo]
Queue Worker
    │
    ├─► Lee job de la cola
    │
    ├─► OpenWAClient::sendTextToChatId()
    │
    ├─► ¿Éxito? → Guardar en BD con status='sent'
    │
    ├─► ¿Error? → Reintentar (3x con backoff 60s)
    │   │
    │   └─► Después fallos → Guardar como 'failed'
    │
    └─► Log en storage/logs/openwa.log
```

## Modelos y Relaciones

```
User
├── id
├── name
├── email
├── phone
└── whatsappMessages() ◄─────────┐
                                  │
                          One-to-Many


WhatsappMessage
├── id
├── user_id ◄─────────────────────┘
├── session_id
├── chat_id (formato: 34612345678@c.us)
├── direction (inbound | outbound)
├── message_id (ID en OpenWA)
├── text
├── payload (JSON de OpenWA)
├── status (pending | sent | delivered | read | failed)
├── error_message
├── sent_at
├── received_at
├── delivered_at
├── read_at
├── created_at
├── updated_at

Scopes:
├── inbound()
├── outbound()
├── forSession($sessionId)
├── forChat($chatId)
└── forUser($userId)

Methods:
├── markAsSent($messageId)
├── markAsDelivered()
├── markAsRead()
└── markAsFailed($errorMessage)
```

## Pipeline de Validación de Webhook

```
HTTP Request
    │
    ▼
OpenWAWebhookController::handle()
    │
    ├─► 1. Validar Firma HMAC
    │   │
    │   ├─► Si config('openwa.webhook_secret') es null
    │   │   └─► Saltar validación
    │   │
    │   ├─► Si existe header 'X-HMAC-SHA256'
    │   │   │
    │   │   └─► hash_hmac('sha256', payload, secret)
    │   │       │
    │   │       ├─► ¿Coincide? → Continuar
    │   │       │
    │   │       └─► ¿No coincide? → 401 Unauthorized
    │   │
    │   └─► Sin header → 401 Unauthorized
    │
    ├─► 2. Verificar Idempotencia
    │   │
    │   └─► ¿idempotencyKey o deliveryId ya procesado?
    │       │
    │       ├─► Sí → return 'Already processed'
    │       │
    │       └─► No → Continuar
    │
    ├─► 3. Marcar como Procesado
    │   │
    │   └─► Redis::setex("openwa.webhook.{$eventId}", 86400, true)
    │       │ (O usar Cache/DB para persistencia)
    │
    ├─► 4. Procesar según tipo
    │   │
    │   ├─► message.received → handleMessageReceived()
    │   ├─► message.status → handleMessageStatus()
    │   ├─► session.status → handleSessionStatus()
    │   ├─► session.qr → handleSessionQr()
    │   └─► session.disconnected → handleSessionDisconnected()
    │
    ▼
JSON Response
    └─► { "ok": true }
```

## Flujo de Conversión Teléfono → Chat ID

```
Input: "612345678"
    │
    ├─► Remover caracteres especiales
    │   "612-345-678" → "612345678"
    │
    ├─► Remover 0 inicial (formato español)
    │   "0612345678" → "612345678"
    │
    ├─► Agregar código de país si no existe
    │   "612345678" es <= 9 dígitos
    │   + config('openwa.default_country_code') = "34"
    │   = "34612345678"
    │
    ▼
Output: "34612345678@c.us"
    │
    └─► Listo para enviar a OpenWA
```

## Estados del Mensaje

```
                    ┌─ PENDING ──────┐
                    │                │
                    ▼                ▼
        ┌───────────────────────────────────────┐
        │      Enviado a OpenWA                 │
        └───────────────────────────────────────┘
                    │
                    ▼
             SENT (Guardado)
                    │
         ┌──────────┼──────────┐
         │          │          │
         ▼          ▼          ▼
      (Error)   (Entrega)  (Lectura)
         │          │          │
         ▼          ▼          ▼
      FAILED    DELIVERED     READ
         │          │
         └──────────┴─────► (Estados finales)

Transiciones:
- PENDING → SENT (inmediato)
- SENT → DELIVERED (webhook message.status)
- DELIVERED → READ (webhook message.status)
- Cualquier estado → FAILED (error)
```

## Seguridad - Headers HTTP

```
Request a OpenWA:
┌─────────────────────────────────────────────┐
│ POST /api/sessions/default/messages/send-text
│
│ Headers:
│  X-API-Key: {OPENWA_API_KEY}     [De config]
│  Content-Type: application/json
│  User-Agent: Laravel HttpClient
│  Accept: application/json
│
│ Body:
│  {
│    "chatId": "34612345678@c.us",
│    "text": "Mensaje..."
│  }
└─────────────────────────────────────────────┘

Webhook desde OpenWA:
┌──────────────────────────────────────┐
│ POST /api/webhooks/openwa
│
│ Headers:
│  X-HMAC-SHA256: {signature}  [Si WEBHOOK_SECRET]
│  Content-Type: application/json
│
│ Body:
│  {
│    "event": "message.received",
│    "idempotencyKey": "unique-key",
│    "message": { ... }
│  }
│
│ HMAC Validation:
│  hash_hmac('sha256', body, WEBHOOK_SECRET) === X-HMAC-SHA256
└──────────────────────────────────────┘
```

## Logging

```
storage/logs/openwa.log:

[2026-05-21 14:30:45] openwa.INFO: OpenWA Request: POST /api/sessions/default/messages/send-text
  {"payload":{"chatId":"34612345678@c.us","text":"Hola mundo"}}

[2026-05-21 14:30:46] openwa.INFO: OpenWA Response: sendTextToChatId
  {"status":200,"data":{"id":"msg-123","status":"sent"}}

[2026-05-21 14:31:00] openwa.INFO: Webhook received
  {"type":"message.received","event_id":"unique-123"}

[2026-05-21 14:31:00] openwa.INFO: Message received
  {"chat_id":"34612345678@c.us","message_id":"msg-456","text":"Hola desde WhatsApp"}

[2026-05-21 14:31:30] jobs.ERROR: Failed to send WhatsApp message
  {"message_id":2,"error":"OpenWA error: Invalid chatId"}

NO SE LOGUEA:
- OPENWA_API_KEY (redacted)
- Datos sensibles del usuario
- Tokens
```

---

Esta arquitectura garantiza que tu aplicación Laravel puede enviar y recibir mensajes de WhatsApp de forma segura, escalable y confiable.

