# Gestor Usuarios - Documentacion Tecnica Completa

Fecha de referencia: 2026-06-06

## 1. Resumen

`gestor_usuarios` es una aplicacion Laravel para centralizar la gestion de personas en varios sistemas internos (usuarios, trabajadores, fichajes, OpenWA y automatizaciones). El sistema unifica registros de distintas bases de datos con una estrategia de vinculacion por UUID.

## 2. Stack tecnico

- Backend: Laravel 12
- Frontend principal: Blade + Tailwind + Alpine.js
- Frontend secundario (TimeGuard): React + Vite (`maria app/`)
- DB: MySQL (multiples conexiones) y SQLite para desarrollo local
- Colas: Laravel Queue con driver `database`
- Scheduler: `php artisan schedule:work`
- Contenedores: Nginx, PHP-FPM, MySQL, Node, Queue Worker, Scheduler

Archivos clave:
- `composer.json`
- `package.json`
- `docker-compose.yml`
- `config/database.php`
- `config/queue.php`

## 3. Arquitectura funcional

### 3.1 Modulos principales

1) Gestion de usuarios y vinculaciones
- Controlador: `app/Http/Controllers/UsuarioController.php`
- Servicio: `app/Services/VinculacionService.php`
- Modelo nexo: `app/Models/UsuarioVinculado.php`

2) Trabajadores y ausencias
- Controlador: `app/Http/Controllers/TrabajadorController.php`
- Servicio: `app/Services/AusenciasService.php`

3) Fichajes y bienestar
- Controladores: `app/Http/Controllers/FichajeController.php`, `app/Http/Controllers/FichajesDiariosController.php`
- Servicios: `app/Services/FichajesService.php`, `app/Services/BienestarService.php`

4) TimeGuard API
- Controlador: `app/Http/Controllers/TimeguardController.php`
- Modelo: `TimeguardWorker`, `TimeguardTimeEntry`, `TimeguardCompensation`, `TimeguardAuditLog`

5) OpenWA y automatizaciones
- Webhook: `app/Http/Controllers/OpenWAWebhookController.php`
- Colaboraciones: `app/Http/Controllers/OpenWACollaborationController.php`
- Automatizaciones: `app/Http/Controllers/AutomationSequenceController.php`
- Jobs: `SendWhatsappMessageJob`, `SendWhatsappFileJob`, `SendAutomaticMessageStepJob`

### 3.2 Estrategia de vinculacion

La tabla `usuarios_vinculados` actua como hub. Cada persona se representa por un `uuid` y puede tener IDs en varios sistemas (`usuario_id`, `trabajador_id`, `pluton_id`, `user_cronos_id`, etc.).

## 4. Rutas y acceso

### 4.1 Web (`routes/web.php`)

- El bloque principal usa middleware `auth`.
- Login y registro usan `guest` + throttle.
- Modulos: usuarios, fichajes, OpenWA, automation, RRHH, tacografo, grupos.

### 4.2 API (`routes/api.php`)

- TimeGuard bajo `throttle:timeguard-api`.
- Webhooks OpenWA sin CSRF y con `throttle:openwa-webhook`.

Endpoints API documentados en Swagger:
- `docs/openapi.yaml`

## 5. Seguridad y hardening actual

### 5.1 Webhook OpenWA

- Validacion HMAC por header `X-HMAC-SHA256` si `OPENWA_WEBHOOK_SECRET` esta configurado.
- Idempotencia persistente con cache: evita reprocesar eventos duplicados.
- TTL de idempotencia configurable:
  - `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS` (default 86400)

### 5.2 Rate limiting

Definido en `app/Providers/AppServiceProvider.php`:
- `openwa-webhook`: por IP (default 120 req/min configurable)
- `timeguard-api`: por IP (120 req/min)

### 5.3 Docker y permisos

En `docker-compose.yml`:
- `storage` y `bootstrap/cache` se inicializan con `www-data`.
- Permisos de logs endurecidos a `775`.
- `queue` y `scheduler` dependen de `php` para evitar arranque temprano.

## 6. Datos y conexiones

Conexiones definidas en `config/database.php`:
- `mysql` (principal)
- `mysql_polifonia`
- `mysql_pluton`
- `mysql_buscador`
- `mysql_cronos`
- `mysql_semillas`
- `mysql_store`
- `mysql_zeus`
- `mysql_trabajadores`
- `mysql_fichajes`

## 7. Cola y jobs

Configuracion: `config/queue.php`
- Driver default: `database`
- Tabla jobs: `jobs`
- Tabla fallidos: `failed_jobs`

Servicios en Docker:
- `queue`: ejecuta `queue:work` en bucle
- `scheduler`: ejecuta `schedule:work`

## 8. Frontend

### 8.1 Blade principal

- Layouts y vistas en `resources/views/`
- Estilos y JS via Vite:
  - `resources/css/app.css`
  - `resources/js/app.js`

### 8.2 Maria App (React)

- Ubicacion: `maria app/`
- Build desplegado en `public/maria-app/`
- Ruta publica: `/maria-app/*`

## 9. Configuracion de entorno recomendada

Variables importantes:
- Core Laravel: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`
- DB principal: `DB_*`
- OpenWA:
  - `OPENWA_BASE_URL`
  - `OPENWA_API_KEY`
  - `OPENWA_SESSION_ID`
  - `OPENWA_WEBHOOK_SECRET`
  - `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS`
  - `OPENWA_WEBHOOK_THROTTLE_PER_MINUTE`

## 10. Arranque y despliegue (Docker)

```bash
docker compose up -d --build php nginx queue scheduler node db
docker exec php_gestor php artisan optimize:clear
docker exec php_gestor php artisan migrate --force
```

## 11. Observabilidad y operaciones

Logs:
- Laravel general: `storage/logs/laravel-YYYY-MM-DD.log`
- Canal OpenWA: `storage/logs/openwa-YYYY-MM-DD.log`

Checks recomendados:
- Estado de rutas API:
```bash
docker exec php_gestor php artisan route:list --path=api -vv
```
- Worker activo:
```bash
docker exec queue_gestor sh -c "test -f /var/www/html/storage/framework/queue-worker-heartbeat && echo ok || echo missing"
```

## 12. Testing

Ubicacion:
- `tests/Feature/`
- `tests/Unit/`

Suite webhook OpenWA:
```bash
docker exec php_gestor php artisan test --filter=OpenWAWebhookControllerTest
```

## 13. Swagger / OpenAPI

Archivo fuente:
- `docs/openapi.yaml`

Incluye:
- API TimeGuard completa (workers, entries, compensations, import)
- Webhooks OpenWA (`/api/webhooks/openwa` y alias legacy)
- Esquemas de request/response y errores de validacion

## 14. Limitaciones conocidas

- La API en `routes/api.php` no exige autenticacion por token actualmente; se protege por red, rate limit y validaciones.
- Para despliegues multi-instancia, usar cache compartida (ej. Redis) para idempotencia global.
- El endpoint list de TimeGuard filtra por `worker_id`; sin query puede devolver resultados no esperados segun datos existentes.

## 15. Referencias rapidas

- `README.md`
- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/TimeguardController.php`
- `app/Http/Controllers/OpenWAWebhookController.php`
- `config/openwa.php`
- `docker-compose.yml`
- `docs/openapi.yaml`

