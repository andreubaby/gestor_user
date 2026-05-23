# 📋 OpenWA - Configuración de Cola de Mensajes

## Problema: Mensajes "Encolados" que No Se Envían

Cuando envías un mensaje desde el panel de colaboraciones, aparece como "encolado" pero **no se envía al usuario** automáticamente.

### ¿Por qué sucede esto?

Los mensajes se envían de forma **asincrónica** (en segundo plano) usando una **cola de trabajos** (Queue). Esto permite que tu aplicación sea rápida sin esperar a que WhatsApp responda.

### Flujo del Sistema:

```
1. Haces clic en "Enviar"
   ↓
2. Mensaje se guarda en BD con status "pending"
   ↓
3. Trabajo (Job) se encola en la cola
   ↓
4. [AQUÍ NECESITAS UN WORKER] → El worker procesa el Job
   ↓
5. Mensaje se envía a WhatsApp
   ↓
6. Estado cambia a "sent" o "failed"
```

## ✅ Solución: Ejecutar el Queue Worker

Para que los mensajes se envíen realmente, **debes ejecutar un worker que procese la cola**.

### Opción 1: Desarrollo Local (Recomendado)

En una **nueva terminal/ventana de PowerShell**:

```powershell
cd C:\Users\alejandro\Documents\Proyectos\gestor_usuarios
php artisan queue:work
```

**Mantén esto corriendo en segundo plano mientras uses la aplicación.**

### Opción 2: Producción (Supervisord)

En tu servidor, instala **Supervisord** para ejecutar el worker automáticamente:

```bash
# Instalar supervisord
sudo apt-get install supervisor

# Crear archivo de configuración
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Contenido del archivo:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```

Luego reinicia supervisord:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### Opción 3: Docker (Si usas containers)

En tu `docker-compose.yml` o `docker-compose.openwa.yml`:

```yaml
queue-worker:
  build: .
  command: php artisan queue:work
  depends_on:
    - mysql
    - redis
  volumes:
    - .:/var/www/html
  environment:
    - QUEUE_CONNECTION=redis
```

## 📊 Monitorar la Cola

### Ver trabajos pendientes:

```powershell
# Listar trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Limpiar trabajos fallidos
php artisan queue:flush
```

### Ver estado en tiempo real:

```powershell
php artisan queue:work --verbose
```

## 🔧 Configuración actual

Tu aplicación está configurada con:

- **Queue Driver**: `{{ config('queue.default') }}` (probablemente `database` o `redis`)
- **Reintentos**: 3 intentos
- **Espera entre reintentos**: 60 segundos
- **Estado inicial**: `pending` (encolado)
- **Estado final**: `sent` (enviado)

## 📱 Nuevo Formato de Teléfono

El campo de teléfono ha sido mejorado para:

✅ Mostrar **+34** automáticamente  
✅ Formatear dígitos en grupos de 3: `622 435 165`  
✅ Solo aceptar dígitos españoles (9 dígitos sin el país)

**Ejemplo de uso:**
- Escribe: `622435165`
- Se formatea a: `+34 622 435 165` (se envía como `34622435165`)

## 🚀 Pasos Rápidos para Empezar

1. **Abre una nueva terminal** y navega al proyecto
2. **Ejecuta**: `php artisan queue:work`
3. **Vuelve a enviar mensajes** desde el panel
4. **Los mensajes se procesarán en tiempo real**

## 🐛 Troubleshooting

### "No hay cola trabajando"
```powershell
# Verifica que el worker esté activo
# Deberías ver: "Listening on: database"
php artisan queue:work --verbose
```

### "El mensaje sigue encolado"
```powershell
# Reintentar manualmente
php artisan queue:retry all
```

### "Error: No such file or directory"
```powershell
# Asegúrate de estar en el directorio correcto
cd C:\Users\alejandro\Documents\Proyectos\gestor_usuarios
```

## 📚 Documentación Oficial

- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [Redis Configuration](https://laravel.com/docs/11.x/redis)
- [Supervisor Configuration](http://supervisord.org/)

---

**Última actualización**: 21 de Mayo de 2026

Si los mensajes aún no se envían después de ejecutar el worker, **revisa los logs**:

```powershell
# Ver logs en tiempo real
Get-Content -Path "storage\logs\laravel.log" -Tail 50 -Wait
```

