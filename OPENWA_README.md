# Integración OpenWA WhatsApp en Laravel

Guía completa para usar la integración de OpenWA como gateway de WhatsApp en tu aplicación Laravel dockerizada.

## 📋 Tabla de Contenidos

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Uso](#uso)
- [Docker](#docker)
- [API](#api)
- [Webhooks](#webhooks)
- [Tests](#tests)
- [Troubleshooting](#troubleshooting)

## 📦 Requisitos

- Laravel 11+
- OpenWA ejecutándose (en contenedor o host)
- PHP 8.2+
- MySQL/SQLite para persistencia

## 🚀 Instalación

### 1. Variables de Entorno

Copiar variables a `.env`:

```env
# Configuración OpenWA
OPENWA_BASE_URL=http://openwa:3000      # URL del servicio OpenWA
OPENWA_API_KEY=your-api-key-here         # API Key generada en OpenWA admin
OPENWA_SESSION_ID=default                # ID de sesión en OpenWA
OPENWA_WEBHOOK_SECRET=your-webhook-secret-here  # Secreto para validar webhooks
OPENWA_REQUEST_TIMEOUT=30000             # Timeout en ms
OPENWA_DEFAULT_COUNTRY_CODE=34           # Código de país por defecto
```

### 2. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto crea:
- `whatsapp_messages` - tabla para persistir mensajes
- `users.phone` - columna fecha en usuarios

## ⚙️ Configuración

### Variables de Entorno Detalladas

#### `OPENWA_BASE_URL`

**URL del servidor OpenWA.**

En Docker hay dos opciones:

**Opción A: OpenWA en otro contenedor**
```env
OPENWA_BASE_URL=http://openwa:3000
```

En `docker-compose.yml`:
```yaml
services:
  openwa:
    image: open-wa/wa-automate:latest
    container_name: openwa
    ports:
      - "3000:3000"
    environment:
      API_KEY: "your-api-key"
      # ... más config
    networks:
      - laravel

  laravel:
    # ... tu config
    networks:
      - laravel
```

**Opción B: OpenWA en el host (desarrollo Windows)**
```env
OPENWA_BASE_URL=http://host.docker.internal:3000
```

El contenedor Laravel accede al host via `host.docker.internal`.

#### `OPENWA_API_KEY`

**Clave API para autenticar requests.**

Se envía como header: `X-API-Key: {value}`

Obtenerla desde admin de OpenWA.

#### `OPENWA_SESSION_ID`

**ID de sesión en OpenWA.**

Cada sesión es una instancia de WhatsApp independiente.

Default: `default`

Para múltiples canales:
```env
OPENWA_SESSION_ID=sales
OPENWA_SESSION_ID=support
```

#### `OPENWA_WEBHOOK_SECRET`

**Secreto para validar HMAC de webhooks.**

Si está configurado, cada webhook debe incluir header:
```
X-HMAC-SHA256: {signature}
```

Si no está configurado, desactiva validación de HMAC.

## 💬 Uso

### Enviar Mensaje Simple

```php
use App\Services\OpenWA\OpenWAClient;

$client = new OpenWAClient();

// Por número de teléfono
$response = $client->sendText('612345678', 'Hola mundo');

// Por Chat ID (formato WhatsApp)
$response = $client->sendTextToChatId('34612345678@c.us', 'Hola mundo');
```

### Enviar Mensaje Asincrónico

Usa Jobs para no bloquear el request:

```php
use App\Jobs\SendWhatsappMessageJob;

// Enviar a número
SendWhatsappMessageJob::dispatch('612345678', 'Mensaje importante', $user->id);

// Enviar a Chat ID
SendWhatsappMessageJob::dispatch('34612345678@c.us', 'Mensaje', $userId);
```

La aplicación:
- Reintenta 3 veces con backoff de 60s
- Guarda mensajes en DB (tabla `whatsapp_messages`)
- Loguea toda actividad

### Usar WhatsappNotificationService

Servicio de dominio que encapsula la lógica de mensajes:

```php
use App\Services\WhatsApp\WhatsappNotificationService;

$notifier = app(WhatsappNotificationService::class);

// Bienvenida
$notifier->sendWelcomeMessage($user);

// OTP
$notifier->sendOtp($user, '123456');

// Actualización de orden
$notifier->sendOrderUpdate($user, [
    'id' => 'ORD-12345',
    'status' => 'Enviado',
    'total' => 99.99,
]);

// Genérico
$notifier->sendToUser($user, 'Mensaje personalizado');
$notifier->sendToPhone('612345678', 'Directo a número');
```

### Obtener Estado de Sesión

```php
$client = new OpenWAClient();
$session = $client->getSession();

// Respuesta:
// {
//    "sessionId": "default",
//    "status": "CONNECTED",
//    "isConnected": true,
//    "batteryLevel": 45,
//    ...
// }
```

### Registrar Webhook

```php
$client = new OpenWAClient();

$response = $client->registerWebhook(
    url: 'https://app.com/api/webhooks/openwa',
    events: ['message.received', 'session.status'],
    secret: env('OPENWA_WEBHOOK_SECRET')
);
```

## 🪝 Webhooks

### Endpoint

```
POST /api/webhooks/openwa
```

Laravel automáticamente:
- Valida firma HMAC (si está configurado)
- Verifica idempotencia (evita duplicados)
- Procesa eventos

### Eventos Soportados

#### `message.received`

Cuando llegas mensaje de WhatsApp:

```json
{
  "event": "message.received",
  "session": "default",
  "message": {
    "id": "wamid.xxx",
    "from": "34612345678@c.us",
    "body": "Contenido del mensaje",
    "timestamp": 1234567890
  }
}
```

Acción: Guarda en tabla `whatsapp_messages` con `direction = 'inbound'`

#### `message.status`

Actualización de estado:

```json
{
  "event": "message.status",
  "messageId": "wamid.xxx",
  "status": "delivered"
}
```

Estados: `sent`, `delivered`, `read`, `failed`

#### `session.status`

Cambio de estado de sesión:

```json
{
  "event": "session.status",
  "session": "default",
  "status": "CONNECTED"
}
```

#### `session.qr`

Nuevo QR (para conectar WhatsApp):

```json
{
  "event": "session.qr",
  "session": "default",
  "qr": "data:image/png;base64,..."
}
```

#### `session.disconnected`

Sesión desconectada:

```json
{
  "event": "session.disconnected",
  "session": "default"
}
```

### Validación de Webhook

Si necesitas verificar la firma:

```php
// En OpenWAWebhookController:
$secret = config('openwa.webhook_secret');

if (!$secret) {
    // No validar
    return true;
}

$signature = request()->headers->get('X-HMAC-SHA256');
$payload = request()->getContent();
$expectedSignature = hash_hmac('sha256', $payload, $secret);

return hash_equals($signature, $expectedSignature);
```

### Idempotencia

Cada webhook debe incluir:

```json
{
  "idempotencyKey": "unique-key-123",
  "deliveryId": "delivery-xxx"
}
```

Laravel usa estas claves para evitar procesar duplicados.

## 🐳 Docker

### docker-compose.yml Ejemplo

```yaml
version: '3.8'

services:
  laravel:
    build: .
    container_name: laravel-app
    ports:
      - "8000:8000"
    environment:
      OPENWA_BASE_URL: http://openwa:3000
      OPENWA_API_KEY: ${OPENWA_API_KEY}
    networks:
      - default
    depends_on:
      - openwa

  openwa:
    image: open-wa/wa-automate:latest
    container_name: openwa
    ports:
      - "3000:3000"
    environment:
      API_KEY: ${OPENWA_API_KEY}
      WEBHOOK_URL: http://laravel:8000/api/webhooks/openwa
      # Más variables según OpenWA
    networks:
      - default
    volumes:
      - ./openwa-sessions:/root/.openwa
```

### En Desarrollo (Windows)

Si OpenWA está en tu máquina Windows:

```env
# .env
OPENWA_BASE_URL=http://host.docker.internal:3000
```

Docker automáticamente resuelve `host.docker.internal` → tu máquina Windows.

## 🔌 API

### OpenWAClient Métodos

```php
class OpenWAClient {
    // Enviar por número
    public function sendText(string $phone, string $message): array

    // Enviar por Chat ID
    public function sendTextToChatId(string $chatId, string $message): array

    // Obtener sesión
    public function getSession(): array

    // Registrar webhook
    public function registerWebhook(
        string $url,
        array $events,
        ?string $secret = null
    ): array
}
```

### WhatsappNotificationService Métodos

```php
class WhatsappNotificationService {
    // Mensaje de bienvenida
    public function sendWelcomeMessage(User $user): void

    // Código OTP
    public function sendOtp(User $user, string $code): void

    // Actualización de orden
    public function sendOrderUpdate(User $user, array $orderData): void

    // Genérico a usuario
    public function sendToUser(User $user, string $message, bool $async = true): void

    // Genérico a teléfono
    public function sendToPhone(string $phone, string $message, ?int $userId = null, bool $async = true): void

    // Genérico a Chat ID
    public function sendToChatId(string $chatId, string $message, ?int $userId = null, bool $async = true): void
}
```

## 📊 Base de Datos

### Tabla `whatsapp_messages`

```sql
CREATE TABLE whatsapp_messages (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,                          -- Referencia a usuario
    session_id VARCHAR(255),                 -- ID de sesión OpenWA
    chat_id VARCHAR(255),                    -- Chat ID WhatsApp (34612345678@c.us)
    direction ENUM('inbound', 'outbound'),   -- Dirección
    message_id VARCHAR(255) UNIQUE,          -- ID único OpenWA
    text LONGTEXT,                           -- Contenido
    payload JSON,                            -- Payload completo
    status ENUM(...),                        -- pending, sent, delivered, read, failed
    error_message VARCHAR(255),              -- Mensaje de error
    sent_at TIMESTAMP,
    received_at TIMESTAMP,
    delivered_at TIMESTAMP,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Scopes Útiles

```php
// Mensajes entrantes
WhatsappMessage::inbound()->get();

// Mensajes salientes
WhatsappMessage::outbound()->get();

// Por sesión
WhatsappMessage::forSession('default')->get();

// Por chat
WhatsappMessage::forChat('34612345678@c.us')->get();

// Por usuario
WhatsappMessage::forUser($userId)->get();
```

## 🧪 Tests

Ejecutar tests:

```bash
php artisan test tests/Unit/Services/OpenWA/
php artisan test tests/Feature/Http/Controllers/
php artisan test tests/Unit/Jobs/
```

### Test Unitario del Cliente

```php
public function test_sends_text_message_by_phone_number()
{
    Http::fake(['http://localhost:3000/*' => Http::response([
        'id' => '123456789',
        'status' => 'sent',
    ])]);

    $response = $this->client->sendText('612345678', 'Hola');

    $this->assertIsArray($response);
    $this->assertEquals('123456789', $response['id']);
}
```

### Test de Webhook

```php
public function test_receives_message_webhook()
{
    $response = $this->postJson('/api/webhooks/openwa', [
        'event' => 'message.received',
        'session' => 'default',
        'message' => [
            'id' => '123',
            'from' => '34612345678@c.us',
            'body' => 'Hello',
        ],
    ]);

    $response->assertJson(['ok' => true]);
    $this->assertDatabaseHas('whatsapp_messages', [
        'text' => 'Hello',
    ]);
}
```

## 🐛 Troubleshooting

### "OpenWA configuration missing"

**Problema:** Falta `OPENWA_API_KEY` en `.env`

**Solución:**
```bash
cp .env.example .env
# Editar .env y agregar valores
php artisan config:cache
```

### "Connection refused" a OpenWA

**Problema:** Laravel no puede conectar a OpenWA

En Docker:
- ¿OpenWA está en otro contenedor? Usar nombre del servicio: `http://openwa:3000`
- ¿OpenWA está en el host? Usar `http://host.docker.internal:3000`

**Solución:**
```bash
# Desde dentro del contenedor Laravel
docker exec laravel ping openwa     # Si está en otro contenedor
docker exec laravel ping host.docker.internal  # Si está en host
```

### Webhook no recibe eventos

**Problema:** OpenWA no está enviando webhooks

**Solución:**

1. Registrar webhook:
```php
$client = new OpenWAClient();
$client->registerWebhook(
    'https://tuapp.com/api/webhooks/openwa',
    ['message.received'],
    env('OPENWA_WEBHOOK_SECRET')
);
```

2. Verificar logs:
```bash
tail -f storage/logs/openwa.log
```

3. Validar URL es accesible desde OpenWA

### Mensajes no se guardan en DB

**Problema:** `whatsapp_messages` no tiene datos

**Solución:**

1. ¿Migración ejecutada?
```bash
php artisan migrate
php artisan migrate:status
```

2. ¿Queue funcionando?
```bash
php artisan queue:work
```

3. ¿Job encolado? Ver logs:
```bash
tail -f storage/logs/laravel.log
```

## 📚 Referencias

- [Documentación OpenWA](https://github.com/open-wa/wa-automate-nodejs)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [Laravel Queues](https://laravel.com/docs/queues)

---

**Última actualización:** Mayo 2026  
**Mantenedor:** Tu nombre/equipo

