# Deploy Checklist - Refactor de Rendimiento (2026-06-05)

Este fichero concentra TODO lo necesario para desplegar el refactor de rendimiento ya subido a Git.

## 1) Cambios incluidos en el release

- `app/Services/CatalogosService.php`
  - Cache de catalogos con TTL
  - `select()` de columnas minimas
  - metodo `CatalogosService::invalidate()`
- `app/Http/Controllers/OnboardingController.php`
  - `Mail::send()` -> `Mail::queue()`
- `app/Services/BienestarService.php`
  - agregaciones semanales movidas a SQL (`AVG + GROUP BY`)
- `app/Services/TrabajadoresIndexService.php`
  - filtros empujados a SQL en `buildCollection($search, $activo)`
- `app/Services/FichajesDiariosService.php`
  - extraido nucleo `computeForDate()`
  - nuevo metodo `getRowsForDate(Carbon $date, ...)`
  - nuevo metodo `generateExcelFile(array $params)`
- `app/Services/MissingPunchReminderService.php`
  - deja de crear `Request` artificial
  - usa `getRowsForDate()` directo
- `app/Jobs/GenerateFichajesExcelJob.php`
  - job para generar exportacion Excel en background
- `database/migrations/2026_06_05_000001_add_performance_indexes.php`
  - indices en tablas principales de consulta

## 2) Prechecks antes de desplegar

- [ ] Confirmar variables de entorno de BD (host, puerto, usuario, password) en servidor.
- [ ] Confirmar queue driver configurado en `.env` (`QUEUE_CONNECTION`).
- [ ] Confirmar que hay worker de colas (Supervisor/systemd o proceso manual).
- [ ] Confirmar permisos de escritura en `storage/` y `bootstrap/cache/`.

## 3) Pasos de deploy (orden recomendado)

```powershell
php artisan down
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

> Nota: En local la migracion no se pudo ejecutar por host `mysql_gestor` no resolvible. En servidor deberia correr si la BD esta accesible.

## 4) Levantar/validar workers de cola

Si ya tienes Supervisor, solo recarga/reinicia. Si necesitas validar manualmente:

```powershell
php artisan queue:work --queue=exports,default --sleep=3 --tries=3
```

## 5) Verificacion funcional post-deploy (smoke test)

- [ ] Abrir pantalla que consume catalogos y validar que carga normal.
- [ ] Enviar un onboarding y validar que queda en cola (no bloquea request).
- [ ] Probar listado de trabajadores con filtros `search/activo/grupo`.
- [ ] Probar flujo de recordatorios de fichaje sin errores.
- [ ] Probar exportacion de fichajes (si se ejecuta async, validar job y archivo en `storage/app/exports`).

Comandos utiles de chequeo:

```powershell
php artisan queue:failed
php artisan queue:retry all
php artisan tinker --execute="App\Services\CatalogosService::invalidate();"
```

## 6) Rollback rapido (si algo falla)

1. Revertir release al commit previo estable.
2. Limpiar y regenerar cache:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Si el problema apunta a la migracion de indices, evaluar rollback controlado de esa migracion en ventana de mantenimiento.

## 7) Notas de operacion

- El impacto esperado es menor latencia en listados y menor consumo de memoria en procesos de fichajes/bienestar.
- El cambio de correo a cola requiere worker activo para salida de emails.
- `CatalogosService::invalidate()` puede ejecutarse tras cargas masivas para refrescar cache.

