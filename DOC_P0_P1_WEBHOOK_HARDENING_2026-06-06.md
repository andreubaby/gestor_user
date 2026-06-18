# P0/P1 Hardening aplicado (OpenWA + TimeGuard)

Fecha: 2026-06-06

## Objetivo

Documentar los cambios aplicados para reducir riesgo operativo y mejorar robustez en:

- Webhooks OpenWA (seguridad, deduplicacion, rate limiting)
- API TimeGuard (rate limiting)
- Permisos Docker en logs/storage
- Cobertura de tests del webhook

## Cambios aplicados

### 1) Idempotencia persistente de webhook (P0)

**Archivo:** `app/Http/Controllers/OpenWAWebhookController.php`

Antes:

- Se usaba `static $processedEvents` en memoria del proceso.
- Al reiniciar contenedor/proceso se perdia el estado de deduplicacion.

Ahora:

- Se usa cache con clave `openwa.webhook.processed.{eventId}`.
- Se hace deduplicacion atomica con `Cache::add(...)`.
- TTL configurable por entorno.
- Si hay excepcion durante el procesamiento, se libera la clave para permitir reintento (`Cache::forget(...)`).

Configuracion asociada:

- `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS` (default: `86400`)

---

### 2) Rate limiting dedicado (P0/P1)

**Archivos:**

- `app/Providers/AppServiceProvider.php`
- `routes/api.php`
- `config/openwa.php`

Se crearon limitadores:

- `openwa-webhook`: limite por IP, configurable.
- `timeguard-api`: limite por IP (120 req/min).

Se aplicaron middlewares:

- `POST /api/webhooks/openwa` -> `throttle:openwa-webhook`
- `POST /api/webhook/openwa` (legacy) -> `throttle:openwa-webhook`
- Todas las rutas `/api/timeguard/*` -> grupo `throttle:timeguard-api`

Configuracion asociada:

- `OPENWA_WEBHOOK_THROTTLE_PER_MINUTE` (default: `120`)

---

### 3) Endurecimiento de permisos Docker (P0)

**Archivo:** `docker-compose.yml`

Cambios:

- `chmod -R 777 /var/www/html/storage/logs` -> `chmod -R 775 /var/www/html/storage/logs`
- Se mantiene `chown -R www-data:www-data` en storage/cache.
- Se mantiene limpieza de `public/hot` al arranque.
- `queue` y `scheduler` dependen de `php` para evitar arranques adelantados con permisos sin preparar.

---

### 4) Estabilidad de tests del webhook (P1)

**Archivo:** `tests/Feature/Http/Controllers/OpenWAWebhookControllerTest.php`

Mejoras:

- Limpieza de cache en `setUp()` para evitar contaminacion entre casos.
- IDs de mensaje e idempotencia con `Str::uuid()` para evitar colisiones.
- Ajuste de mocks de logging para evitar falsos negativos cuando Laravel registra errores en test.

Resultado validado:

- `OpenWAWebhookControllerTest`: **7 tests OK**.

## Variables de entorno nuevas/relevantes

Agregar en `.env` (si se quiere override):

```env
OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS=86400
OPENWA_WEBHOOK_THROTTLE_PER_MINUTE=120
```

## Pasos de despliegue/reinicio recomendados

```bash
docker compose up -d --build php queue scheduler node
docker exec php_gestor php artisan config:clear
docker exec php_gestor php artisan route:clear
docker exec php_gestor php artisan optimize:clear
```

## Verificacion rapida

1. Ver middleware aplicado:

```bash
docker exec php_gestor php artisan route:list --path=api/webhooks/openwa -vv
```

Debe mostrar `throttle:openwa-webhook`.

2. Ejecutar tests del webhook:

```bash
docker exec php_gestor php artisan test --filter=OpenWAWebhookControllerTest
```

## Notas operativas

- Para idempotencia compartida entre multiples workers/instancias, conviene usar un store de cache centralizado (ej. Redis).
- En entorno de tests puede usarse cache en memoria (`array`) para velocidad; en produccion es preferible un store persistente/compartido.

## Archivos modificados

- `app/Http/Controllers/OpenWAWebhookController.php`
- `app/Providers/AppServiceProvider.php`
- `routes/api.php`
- `config/openwa.php`
- `docker-compose.yml`
- `tests/Feature/Http/Controllers/OpenWAWebhookControllerTest.php`

