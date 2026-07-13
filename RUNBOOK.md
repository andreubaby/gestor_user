# RUNBOOK — gestor_usuarios

Guía operativa para levantar, desplegar y mantener este proyecto sin depender de quien lo escribió. Va al grano: para arquitectura a fondo usa `docs/PROJECT_DOCUMENTATION.md`; este documento es el "qué hago si..." del día a día.

## 1. Qué es esto en una frase

App Laravel 12 que centraliza usuarios/trabajadores repartidos en 9 sistemas distintos (cada uno su propia BD MySQL), vinculados por `uuid`, más un módulo de mensajería WhatsApp (OpenWA) y una API de fichajes (TimeGuard).

## 2. Dónde está cada documento (mapa)

| Necesito... | Leer |
|---|---|
| Visión general funcional | `README.md` |
| Arquitectura técnica completa (módulos, seguridad, testing) | `docs/PROJECT_DOCUMENTATION.md` |
| Reglas para no romper el flujo de WhatsApp/OpenWA | `AGENTS.md` |
| Detalle de OpenWA (setup, arquitectura, ejemplos) | `OPENWA_*.md` |
| Contrato de la API | `docs/openapi.yaml` (Swagger en `/api/docs`) |
| Este runbook (operación día a día) | este archivo |

Este runbook ya incorpora lo relevante de las antiguas bitácoras de despliegue puntuales (refactor de rendimiento, hardening de webhooks), que se eliminaron por quedar redundantes.

## 3. Arranque local (Docker — recomendado)

```bash
cp .env.example .env
php artisan key:generate     # o edita APP_KEY a mano
docker compose up -d --build php nginx queue scheduler node db
docker exec php_gestor php artisan migrate --force
```

- App: `http://localhost:${APP_PORT:-8077}`
- Contenedores: `nginx_gestor`, `php_gestor`, `queue_gestor`, `scheduler_gestor`, `mysql_gestor` (puerto host `3366`), `node_gestor`.
- `node_gestor` compila dos frontends: el principal (Vite/Blade) y `maria app/` (React, publicado en `public/maria-app/`).

### Arranque local sin Docker (rápido, todo en uno)

```bash
composer install
npm install
composer run dev   # levanta serve + queue:listen + logs (pail) + vite en paralelo
```

## 4. Variables de entorno clave

Además de las estándar de Laravel (`APP_*`, `DB_*`, `SESSION_*`), estas son específicas del proyecto:

| Variable | Para qué | Default |
|---|---|---|
| `OPENWA_BASE_URL` | URL del gateway OpenWA (WhatsApp) | `http://openwa:3000` |
| `OPENWA_API_KEY` | Auth contra OpenWA | — (obligatoria) |
| `OPENWA_SESSION_ID` | Sesión de WhatsApp a usar | `default` |
| `OPENWA_WEBHOOK_SECRET` | Valida firma HMAC del webhook entrante | — (si no está, no se valida HMAC) |
| `OPENWA_REQUEST_TIMEOUT` | Timeout ms hacia OpenWA | `30000` |
| `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS` | TTL de deduplicación de eventos del webhook | `86400` |
| `OPENWA_WEBHOOK_THROTTLE_PER_MINUTE` | Rate limit del webhook por IP | `120` |
| `QUEUE_CONNECTION` | Driver de colas | `database` |

Las 9 conexiones MySQL (una por sistema externo) se configuran en `config/database.php` con sus propias variables `DB_*_HOST/PORT/DATABASE/USERNAME/PASSWORD` por conexión — revisa ese archivo antes de dar de alta un entorno nuevo, no asumas que `DB_*` genérico basta.

## 5. Mapa de conexiones de base de datos

| Conexión (`config/database.php`) | Sistema externo |
|---|---|
| `mysql` | Usuarios principal (BD propia de este gestor) |
| `mysql_polifonia` | Polifonía (trabajadores, ausencias) |
| `mysql_pluton` | Plutón |
| `mysql_buscador` | Buscador (usuarios y trabajadores) |
| `mysql_cronos` | Cronos (fichajes legacy) |
| `mysql_semillas` | Semillas |
| `mysql_store` | Store |
| `mysql_zeus` | Zeus |
| `mysql_trabajadores` | Trabajadores (DB10) |
| `mysql_fichajes` | Fichajes / Bienestar (DB11) |

Regla de oro (ver `AGENTS.md`): nunca asumas la conexión por defecto, cada modelo/consulta debe indicar explícitamente `->on('mysql_xxx')` cuando toque datos de otro sistema.

## 6. Despliegue a producción

Pasos genéricos (basados en el último despliegue real registrado):

**Prechecks**
- Variables de BD de destino confirmadas en el servidor.
- `QUEUE_CONNECTION` correcto en `.env`.
- Worker de colas activo (contenedor `queue` o Supervisor/systemd).
- Permisos de escritura en `storage/` y `bootstrap/cache/`.

**Deploy**
```bash
docker compose up -d --build php queue scheduler node
docker exec php_gestor php artisan migrate --force
docker exec php_gestor php artisan optimize:clear
docker exec php_gestor php artisan config:cache
docker exec php_gestor php artisan route:cache
docker exec php_gestor php artisan view:cache
```

**Smoke test post-deploy**
- Cargar una pantalla que use catálogos (valida cache).
- Enviar un onboarding y comprobar que queda en cola, no bloquea el request.
- Probar listado de trabajadores con filtros.
- Probar envío de WhatsApp desde `/openwa/colaboraciones` (ver §8).
- Revisar `php artisan queue:failed` — debe seguir vacío o sin crecer.

**Rollback**
```bash
# revertir al commit/release anterior, luego:
docker exec php_gestor php artisan optimize:clear
docker exec php_gestor php artisan config:cache
docker exec php_gestor php artisan route:cache
docker exec php_gestor php artisan view:cache
```
Si el problema es una migración de índices, evaluar rollback de esa migración concreta en ventana de mantenimiento.

## 7. Colas y workers

- Driver: `database` (tablas `jobs` / `failed_jobs`).
- En Docker, el contenedor `queue` corre en bucle `queue:work --once` (se reinicia solo si un job muere) y escribe un heartbeat en `storage/framework/queue-worker-heartbeat`.
- El contenedor `scheduler` corre `schedule:work` (dispara los comandos programados, ver §9).

Comandos útiles:
```bash
php artisan queue:failed              # ver fallidos
php artisan queue:retry all           # reintentar todos
php artisan queue:flush               # limpiar fallidos
php artisan tinker                    # inspección manual: DB::table('jobs')->get()
```

Verificar que el worker sigue vivo:
```bash
docker exec queue_gestor sh -c "test -f /var/www/html/storage/framework/queue-worker-heartbeat && echo ok || echo missing"
```

Logs:
```bash
docker exec php_gestor tail -n 50 storage/logs/laravel.log
docker exec php_gestor tail -n 50 storage/logs/openwa-$(date +%F).log   # canal específico OpenWA
```

## 8. WhatsApp / OpenWA — lo esencial para operar

Flujo: `Controller/Service → WhatsappNotificationService → SendWhatsappMessageJob (cola) → OpenWAClient → OpenWA API`.
Entrada: `OpenWA webhook (POST /api/webhooks/openwa) → OpenWAWebhookController → actualiza WhatsappMessage`.

Protecciones activas que **no** se deben tocar sin revisar `AGENTS.md` + `OPENWA_ARCHITECTURE.md` primero:
- Validación HMAC del webhook (header `X-HMAC-SHA256`), activa si `OPENWA_WEBHOOK_SECRET` está configurada.
- Idempotencia persistente vía cache (`Cache::add`), TTL en `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS`.
- Rate limit dedicado `throttle:openwa-webhook` (`OPENWA_WEBHOOK_THROTTLE_PER_MINUTE`, default 120/min).
- Chat IDs siempre en formato `{numero}@c.us` — usar `OpenWAClient::phoneToChatId()`, nunca teléfonos en bruto.

Validar configuración y diagnosticar:
```bash
php artisan openwa:validate
php artisan openwa:debug-phone {userId}
```

Prueba manual end-to-end: abrir `/openwa/colaboraciones`, enviar un mensaje, comprobar que pasa de `pending` → `sent` en "Actividad Reciente".

Nota multi-instancia: la idempotencia usa el cache store configurado (`CACHE_STORE`); si se despliega en más de una instancia/worker, migrar a un store compartido (Redis) — con `database`/`array` local no hay deduplicación cruzada entre nodos.

## 9. Tareas programadas (scheduler)

Definidas en `routes/console.php`:

| Comando | Frecuencia | Qué hace |
|---|---|---|
| `app:execute-scheduled-automations` | cada minuto | Ejecuta automatizaciones de WhatsApp programadas |
| `app:send-missing-punch-reminders` | diario, hora en `config('fichajes.missing_punch.schedule_time')` (default 09:00) | Avisa por WhatsApp a quien no fichó |

Otros comandos manuales disponibles (`app/Console/Commands`):

| Comando | Uso |
|---|---|
| `fichajes:sync-users` | Sincroniza usuarios DB10 (trabajadores) → DB11 (fichajes) por email; hace backup antes |
| `fichajes:sync-passwords` | Igual que el anterior pero solo contraseñas |
| `openwa:validate` | Valida configuración OpenWA |
| `openwa:debug-phone {userId}` | Muestra cómo se resuelve el teléfono de un usuario para OpenWA |

## 10. Troubleshooting común

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| Mensajes WhatsApp se quedan en `pending` | Worker de colas caído | Comprobar heartbeat (§7), revisar `queue:failed`, reiniciar contenedor `queue` |
| Webhook OpenWA devuelve 429 | Rate limit alcanzado | Revisar `OPENWA_WEBHOOK_THROTTLE_PER_MINUTE`, tráfico real vs. esperado |
| Webhook procesa el mismo evento dos veces | Cache de idempotencia no compartido entre instancias | Migrar a Redis si hay más de un nodo (ver §8) |
| Migración falla con host no resoluble (ej. `mysql_gestor`) | Se corre fuera del contenedor Docker en vez de dentro | Ejecutar `artisan migrate` vía `docker exec php_gestor ...`, no desde el host |
| Permisos de `storage/logs` | Contenedor `php` no terminó su init (chown/chmod) | Revisar logs de arranque del contenedor `php`, reintentar `docker compose up -d php` |
| Emails no salen | Cambiaron de `Mail::send` a `Mail::queue`; requiere worker activo | Confirmar worker de colas corriendo (§7) |

## 11. Testing

```bash
php artisan test                                          # todo
php artisan test --filter=OpenWAWebhookControllerTest      # solo webhook (zona crítica)
```
Ubicación: `tests/Feature/` y `tests/Unit/`. Antes de tocar el flujo de WhatsApp, correr mínimo la suite de OpenWA (webhook, job de envío, cliente, transición de estados) — ver checklist completo en `AGENTS.md`.

## 12. Deuda técnica / cosas a vigilar

- La API en `routes/api.php` no exige autenticación por token; se protege solo por red + rate limit + validaciones. Si se expone a internet más ampliamente, revisar esto primero.
- Idempotencia del webhook depende del cache store; en multi-instancia usar Redis (ver §8).
- El endpoint de listado de TimeGuard filtra por `worker_id`; sin ese parámetro puede devolver más de lo esperado.
- Este runbook es la referencia vigente para operar colas/workers; cualquier nota suelta anterior sobre monitoreo de cola quedaba obsoleta y se eliminó.
