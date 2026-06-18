# AGENTS.md

## Contexto del proyecto

Este repositorio es un monolito **Laravel 12** que unifica operaciones de usuarios y administración entre varios sistemas de Babyplant. El modelo de integración entre sistemas se basa en vinculación por **UUID** y registros de relación entre usuarios.

La integración más crítica del proyecto es la mensajería de **WhatsApp mediante OpenWA**, usando API HTTP para envío de mensajes y webhooks para recepción de eventos, mensajes y estados.

Áreas principales del backend:

* Controladores HTTP: `app/Http/Controllers`
* Servicios de dominio: `app/Services`
* Jobs asíncronos: `app/Jobs`
* Modelos y persistencia: `app/Models`
* Configuración OpenWA: `config/openwa.php`
* Rutas API: `routes/api.php`

---

## Reglas críticas para agentes

### 1. No asumir una única base de datos

El proyecto usa varias conexiones de base de datos. Cuando consultes datos de usuarios u otros sistemas, revisa primero qué conexión corresponde.

Ejemplo:

```php
Model::on('mysql_polifonia')
```

No sustituyas lecturas multi-DB por consultas sobre la conexión por defecto sin comprobarlo antes.

Archivos relevantes:

* `README.md`
* `config/database.php`
* modelos en `app/Models`

---

### 2. Respetar la vinculación por UUID

La identidad entre sistemas se gestiona mediante registros de vinculación UUID, especialmente en relaciones como `usuario_vinculados`.

Cuando modifiques sincronización, asociación o consulta de usuarios:

* conserva la relación por UUID;
* no reemplaces la lógica por coincidencias débiles como email, nombre o teléfono sin validar;
* revisa primero la documentación del proyecto y los modelos relacionados.

---

### 3. Mantener intacta la lógica crítica de OpenWA

La mensajería WhatsApp es una zona sensible del proyecto. Antes de modificarla, revisa estos archivos:

* `app/Services/WhatsApp/WhatsappNotificationService.php`
* `app/Jobs/SendWhatsappMessageJob.php`
* `app/Services/OpenWA/OpenWAClient.php`
* `app/Http/Controllers/OpenWAWebhookController.php`
* `app/Models/WhatsappMessage.php`
* `config/openwa.php`
* `OPENWA_ARCHITECTURE.md`
* `OPENWA_README.md`

---

## Flujo de WhatsApp

### Envío outbound

Flujo esperado:

```text
Controller / Service
  -> WhatsappNotificationService
  -> SendWhatsappMessageJob
  -> OpenWAClient
  -> OpenWA API
```

La ruta principal de envío debe ser asíncrona mediante cola cuando aplique.

No dupliques llamadas HTTP directas a OpenWA si ya existe funcionalidad en `OpenWAClient`.

---

### Recepción inbound y estados

Flujo esperado:

```text
OpenWA webhook
  -> OpenWAWebhookController::handle()
  -> handlers de mensajes / estados
  -> actualización de WhatsappMessage
```

El webhook debe mantener:

* idempotencia por clave de evento;
* validación HMAC cuando esté configurada;
* actualización controlada del estado del mensaje;
* comportamiento actual de middleware, throttling y CSRF.

No elimines ni debilites estas protecciones.

---

## Estados de mensajes WhatsApp

El ciclo de vida de un mensaje se persiste en `WhatsappMessage`.

Estados habituales:

```text
pending -> sent -> delivered / read
pending -> failed
```

Usa los métodos helper del modelo `WhatsappMessage` para cambiar estados.

No escribas estados manualmente con actualizaciones ad-hoc salvo que no exista alternativa y quede justificado.

Archivo relevante:

* `app/Models/WhatsappMessage.php`

---

## Formato de chat IDs

OpenWA espera identificadores de chat con este formato:

```text
{country_code}{number}@c.us
```

No uses teléfonos en bruto directamente como chat IDs.

Para normalizar números, reutiliza:

```php
OpenWAClient::phoneToChatId()
```

Revisa también:

* `config/openwa.php`

---

## Webhook OpenWA

El webhook de OpenWA está definido como ruta API y está exento de CSRF.

No cambies esta condición sin revisar el impacto completo sobre OpenWA.

Archivos relevantes:

* `routes/api.php`
* `app/Http/Controllers/OpenWAWebhookController.php`
* `config/openwa.php`

---

## Colas y ejecución asíncrona

La cola usa backend de base de datos por defecto.

Comando habitual para worker local:

```bash
php artisan queue:work database --sleep=1 --tries=3 --backoff=60
```

Comandos útiles de diagnóstico:

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan tinker
```

Consulta también:

* `QUEUE_MONITOR.md`
* `config/queue.php`
* `docker-compose.yml`

En producción, el comportamiento depende de los servicios de worker y scheduler definidos en Docker.

---

## Comandos de desarrollo

Servidor local Laravel:

```bash
php artisan serve
```

Servidor frontend:

```bash
npm run dev
```

Build frontend:

```bash
npm run build
```

Stack Docker:

```bash
docker-compose up -d
```

---

## Testing

Existen tests específicos para la integración OpenWA.

Rutas principales:

* `tests/Unit/Services/OpenWA/`
* `tests/Unit/Jobs/SendWhatsappMessageJobTest.php`
* `tests/Feature/Http/Controllers/OpenWAWebhookControllerTest.php`

Cuando modifiques lógica de mensajería, envío, jobs o webhooks, valida como mínimo:

* cliente OpenWA;
* job de envío;
* transición de estados;
* webhook inbound;
* webhook de estados;
* comportamiento idempotente;
* errores de API;
* reintentos o fallos de cola.

Usa `Http::fake()` siguiendo los patrones de los tests existentes para simular respuestas de OpenWA.

---

## Checklist antes de tocar OpenWA

Antes de cambiar código relacionado con WhatsApp:

1. Revisa `OPENWA_ARCHITECTURE.md`.
2. Revisa `config/openwa.php`.
3. Identifica si el flujo es síncrono o asíncrono.
4. Comprueba si ya existe método en `OpenWAClient`.
5. Comprueba si el estado debe cambiarse mediante `WhatsappMessage`.
6. Añade o actualiza tests.
7. Verifica que no se rompe la idempotencia del webhook.
8. Verifica que no se rompe la validación HMAC.
9. Verifica que los chat IDs siguen usando formato `{numero}@c.us`.

---

## Configuración de OpenWA

Variables principales:

```text
OPENWA_BASE_URL
OPENWA_API_KEY
OPENWA_SESSION_ID
OPENWA_WEBHOOK_SECRET
```

La configuración vive en:

```text
config/openwa.php
```

No hardcodees credenciales, URLs, session IDs ni secretos en el código.

---

## Documentación de APIs

Contrato OpenAPI general:

```text
docs/openapi.yaml
```

Documentación específica de OpenWA:

```text
OPENWA_*.md
```

Antes de cambiar contratos públicos o endpoints, revisa y actualiza la documentación correspondiente.

---

## Zonas de riesgo

Evita especialmente:

* saltarte `WhatsappNotificationService`;
* duplicar lógica de `OpenWAClient`;
* escribir estados directamente en base de datos;
* asumir una única conexión MySQL;
* romper relaciones UUID entre sistemas;
* cambiar middleware del webhook sin revisar OpenWA;
* eliminar idempotencia;
* eliminar o relajar validación HMAC;
* hardcodear configuración;
* modificar colas sin probar el worker;
* cambiar formato de chat IDs.

---

## Criterio general

Prioriza cambios pequeños, trazables y cubiertos por tests.

Cuando exista una abstracción ya creada en el proyecto, reutilízala antes de crear una nueva.

Cuando modifiques una integración externa, añade pruebas con `Http::fake()` y cubre tanto el caso correcto como errores de API.
