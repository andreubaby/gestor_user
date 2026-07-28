# 📋 Guía de Instalación — Gestor de Usuarios Babyplant

Esta guía cubre la instalación completa del proyecto desde cero, tanto con Docker (recomendado) como sin Docker para desarrollo local.

---

## 📦 Requisitos previos

### Opción A: Instalación con Docker (Recomendado)

- **Docker** 20.10+
- **Docker Compose** 2.0+
- **Git**
- **Make** (opcional pero recomendado)

### Opción B: Instalación sin Docker

- **PHP** 8.2 o superior
- **Composer** 2.x
- **Node.js** 22.x o superior
- **npm** o **yarn**
- **MySQL** 8.0+ (acceso a las 11 bases de datos)
- **Git**

---

## 🚀 Instalación rápida con Docker

### 1. Clonar el repositorio

```bash
git clone <repository-url> gestor-de-usuarios
cd gestor-de-usuarios
```

### 2. Usar el Makefile (Recomendado)

```bash
make install
```

Esto ejecutará automáticamente:
- Creación del archivo `.env` desde `.env.example`
- Build de las imágenes Docker
- Inicio de los contenedores
- Generación de `APP_KEY`
- Ejecución de migraciones

**La aplicación estará disponible en:** `http://localhost:8077`

### 3. Instalación manual (sin Make)

Si no tienes `make` disponible:

```bash
# 1. Crear .env
cp .env.example .env

# 2. Editar .env con tus valores (ver sección de configuración)
nano .env  # o vim, code, etc.

# 3. Levantar contenedores
docker compose up -d --build php nginx queue scheduler node db

# 4. Esperar a que PHP esté listo
sleep 5

# 5. Generar APP_KEY
docker exec php_gestor php artisan key:generate --force

# 6. Ejecutar migraciones
docker exec php_gestor php artisan migrate --force
```

---

## 🖥️ Instalación sin Docker (Local)

### 1. Clonar y preparar entorno

```bash
git clone <repository-url> gestor-de-usuarios
cd gestor-de-usuarios
cp .env.example .env
```

### 2. Editar configuración de base de datos

Edita `.env` y configura:
- `DB_CONNECTION=mysql` (en lugar de sqlite)
- `DB_HOST=127.0.0.1` (o tu host MySQL)
- `DB_PORT=3306`
- `DB_DATABASE=gestoria` (o el nombre que prefieras)
- `DB_USERNAME` y `DB_PASSWORD`

Configura también las 11 conexiones de bases de datos (ver sección siguiente).

### 3. Instalar dependencias

Con el Makefile:
```bash
make install-local
```

O manualmente:
```bash
composer install
npm install
cd "maria app" && npm install && cd ..
php artisan key:generate
php artisan migrate
npm run build
cd "maria app" && npm run build && cd ..
```

### 4. Arrancar el entorno de desarrollo

```bash
make dev
```

Esto arranca en paralelo:
- Servidor PHP (`php artisan serve`)
- Worker de colas (`php artisan queue:listen`)
- Logs en tiempo real (`php artisan pail`)
- Servidor Vite (frontend)

**La aplicación estará disponible en:** `http://localhost:8000`

---

## ⚙️ Configuración de variables de entorno

### Variables esenciales

Edita `.env` y configura como mínimo:

```env
APP_NAME="Gestor de Usuarios"
APP_ENV=local  # o 'production' en producción
APP_DEBUG=true  # false en producción
APP_KEY=  # se genera automáticamente con php artisan key:generate
APP_URL=http://localhost:8077

# Zona horaria y localización
APP_TIMEZONE=Europe/Madrid
APP_LOCALE=es

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=debug  # warning en producción
```

### Base de datos principal

```env
DB_CONNECTION=mysql
DB_HOST=db  # 'db' en Docker, '127.0.0.1' en local
DB_PORT=3306
DB_DATABASE=gestoria
DB_USERNAME=gestoria
DB_PASSWORD=TU_PASSWORD_AQUI
DB_ROOT_PASSWORD=TU_ROOT_PASSWORD_AQUI
```

### 🗄️ Conexiones a múltiples sistemas

El proyecto requiere acceso a **11 bases de datos diferentes**. Configura cada una en `.env`:

| Sistema | Conexión | Variables |
|---------|----------|-----------|
| **Gestor principal** | `mysql` | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| **Polifonía** | `mysql_polifonia` | `DB2_HOST`, `DB2_PORT`, `DB2_DATABASE`, `DB2_USERNAME`, `DB2_PASSWORD` |
| **Plutón** | `mysql_pluton` | `DB4_HOST`, `DB4_PORT`, `DB4_DATABASE`, `DB4_USERNAME`, `DB4_PASSWORD` |
| **Buscador** | `mysql_buscador` | `DB5_HOST`, `DB5_PORT`, `DB5_DATABASE`, `DB5_USERNAME`, `DB5_PASSWORD` |
| **Cronos** | `mysql_cronos` | `DB6_HOST`, `DB6_PORT`, `DB6_DATABASE`, `DB6_USERNAME`, `DB6_PASSWORD` |
| **Store** | `mysql_store` | `DB7_HOST`, `DB7_PORT`, `DB7_DATABASE`, `DB7_USERNAME`, `DB7_PASSWORD` |
| **Zeus** | `mysql_zeus` | `DB8_HOST`, `DB8_PORT`, `DB8_DATABASE`, `DB8_USERNAME`, `DB8_PASSWORD` |
| **Semillas** | `mysql_semillas` | `DB9_HOST`, `DB9_PORT`, `DB9_DATABASE`, `DB9_USERNAME`, `DB9_PASSWORD` |
| **Trabajadores** | `mysql_trabajadores` | `DB10_HOST`, `DB10_PORT`, `DB10_DATABASE`, `DB10_USERNAME`, `DB10_PASSWORD` |
| **Fichajes** | `mysql_fichajes` | `DB11_HOST`, `DB11_PORT`, `DB11_DATABASE`, `DB11_USERNAME`, `DB11_PASSWORD` |
| **Default (legacy)** | `mysql` (DB3) | `DB3_HOST`, `DB3_PORT`, `DB3_DATABASE`, `DB3_USERNAME`, `DB3_PASSWORD` |

**Ejemplo de configuración:**

```env
# DB2 - Polifonía (trabajadores, ausencias)
DB2_HOST=185.14.56.19
DB2_PORT=3306
DB2_DATABASE=camioneros
DB2_USERNAME=polifonia_user
DB2_PASSWORD=tu_password

# DB3 - Default
DB3_HOST=172.16.0.41
DB3_PORT=3306
DB3_DATABASE=default
DB3_USERNAME=baby
DB3_PASSWORD=tu_password

# DB4 - Plutón
DB4_HOST=185.14.58.172
DB4_PORT=3306
DB4_DATABASE=pluton
DB4_USERNAME=Pol1fon14
DB4_PASSWORD=tu_password

# ... y así sucesivamente para DB5-DB11
```

### Colas y caché

```env
QUEUE_CONNECTION=database  # o 'redis' si usas Redis
CACHE_STORE=database       # o 'redis' si usas Redis
SESSION_DRIVER=database
```

### 📧 Email (opcional)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD="tu_app_password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 📱 OpenWA (WhatsApp) — Configuración avanzada

**Variables obligatorias:**

```env
OPENWA_BASE_URL=http://openwa:3000  # En Docker
# OPENWA_BASE_URL=http://host.docker.internal:3000  # Si OpenWA corre en Windows
# OPENWA_BASE_URL=http://localhost:3000  # Si OpenWA corre en local

OPENWA_API_KEY=tu-api-key-aqui
OPENWA_SESSION_ID=default
OPENWA_REQUEST_TIMEOUT=30000
OPENWA_DEFAULT_COUNTRY_CODE=34  # España
```

**Variables opcionales:**

```env
# Validación HMAC del webhook (recomendado en producción)
OPENWA_WEBHOOK_SECRET=tu-webhook-secret-aqui

# Deduplicación de eventos del webhook
OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS=86400  # 24 horas

# Rate limiting del webhook
OPENWA_WEBHOOK_THROTTLE_PER_MINUTE=120
```

**Variables de recordatorios de fichaje:**

```env
MISSING_PUNCH_REMINDER_ENABLED=true
MISSING_PUNCH_REMINDER_TIME=09:30
MISSING_PUNCH_REMINDER_TEMPLATE="Hola {nombre}, ayer ({fecha}) no aparece ningún fichaje tuyo. Si corresponde, revísalo en la app."
```

---

## 🐳 Docker: Arquitectura de servicios

Cuando levantes el stack con `docker compose up -d`, se crearán estos servicios:

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **nginx** | 8077, 5445 | Servidor web (HTTP + HTTPS) |
| **php** | - | PHP-FPM para ejecutar Laravel |
| **queue** | - | Worker de colas (procesa jobs asíncronos) |
| **scheduler** | - | Scheduler de Laravel (tareas programadas) |
| **node** | - | Compila assets (Vite) |
| **db** | 3366 → 3306 | MySQL 8.0 (solo para la BD principal) |

**Nota:** El servicio `db` solo gestiona la base de datos principal del gestor. Las otras 10 conexiones apuntan a servidores externos que debes tener configurados.

---


## 🔌 OpenWA: Instalación y configuración detallada

OpenWA es el gateway de WhatsApp que permite enviar y recibir mensajes. Hay tres formas de instalarlo.

### Paso previo: Configurar variables de entorno

Antes de instalar OpenWA, asegúrate de tener estas variables en tu `.env`:

```env
# URL del servidor OpenWA
OPENWA_BASE_URL=http://openwa:3000  # Si usas Docker
# OPENWA_BASE_URL=http://host.docker.internal:3000  # Si OpenWA corre en Windows/macOS
# OPENWA_BASE_URL=http://localhost:3000  # Si todo corre en local

# Credenciales
OPENWA_API_KEY=your-api-key-here
OPENWA_SESSION_ID=default
OPENWA_WEBHOOK_SECRET=your-webhook-secret-here

# Configuración adicional
OPENWA_REQUEST_TIMEOUT=30000
OPENWA_DEFAULT_COUNTRY_CODE=34

# Rate limiting y deduplicación
OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS=86400
OPENWA_WEBHOOK_THROTTLE_PER_MINUTE=120
```

---

### Opción A: OpenWA con Docker Compose (Recomendado para producción)

El proyecto incluye `docker-compose.openwa.yml` que levanta OpenWA como contenedor.

**1. Verificar que Laravel está corriendo:**

```bash
make ps
# Debe mostrar los servicios activos (nginx, php, queue, etc.)
```

**2. Configurar variables en `.env`:**

```env
OPENWA_BASE_URL=http://openwa:3000
OPENWA_API_KEY=tu-api-key-segura
OPENWA_SESSION_ID=default
OPENWA_WEBHOOK_SECRET=tu-webhook-secret-seguro
```

**3. Levantar el stack OpenWA:**

```bash
make up-openwa
```

Esto iniciará:
- Contenedor `openwa-gateway` en el puerto 3000
- Volúmenes persistentes para sesiones
- Conexión a la red de Laravel

**4. Verificar que OpenWA está activo:**

```bash
docker ps | grep openwa
# Debe mostrar: openwa-gateway ... Up

docker logs openwa-gateway
# Debe mostrar los logs de inicio
```

**5. Comprobar conectividad desde Laravel:**

```bash
docker exec php_gestor curl -H "X-API-Key: tu-api-key-segura" http://openwa:3000/api/health
# Debe devolver: {"status":"ok",...}
```

**6. Escanear QR para conectar WhatsApp:**

Accede a la interfaz web de OpenWA:

```
http://localhost:3000/
```

Escanea el código QR con tu WhatsApp:
1. Abre WhatsApp en tu teléfono
2. Ve a **Configuración** → **Dispositivos vinculados**
3. Toca **Vincular un dispositivo**
4. Escanea el QR que aparece en `http://localhost:3000`

Espera a que el estado cambie a **CONNECTED**.

**7. Registrar el webhook en Laravel:**

```bash
docker exec php_gestor php artisan openwa:register-webhook
```

Esto configura OpenWA para que envíe eventos (mensajes entrantes, estados de entrega) a Laravel.

**8. Validar configuración completa:**

```bash
make openwa-validate
```

Debe mostrar:
```
✓ OPENWA_BASE_URL configurado
✓ OPENWA_API_KEY configurado
✓ Conexión a OpenWA exitosa
✓ Sesión CONNECTED
✓ Webhook registrado
```

---

### Opción B: OpenWA en host Windows (Desarrollo local)

Si prefieres ejecutar OpenWA directamente en tu máquina Windows fuera de Docker.

**1. Instalar OpenWA globalmente:**

```powershell
npm install -g @open-wa/wa-automate
```

**2. Crear archivo de configuración:**

Crea un archivo `openwa-config.js`:

```javascript
module.exports = {
  sessionId: 'default',
  headless: true,
  qrTimeout: 0,
  authTimeout: 0,
  restartOnCrash: true,
  cacheEnabled: false,
  useChrome: true,
  killProcessOnBrowserClose: true,
  throwErrorOnTosBlock: false,
  chromiumArgs: [
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
    '--disable-accelerated-2d-canvas',
    '--no-first-run',
    '--no-zygote',
    '--disable-gpu'
  ],
  logConsole: true,
  logConsoleErrors: true,
  popup: true,
  apiHost: 'localhost',
  apiPort: 3000,
  apiKey: 'your-api-key-here',
  webhook: {
    url: 'http://host.docker.internal:8077/api/webhooks/openwa',
    events: ['message.received', 'message.status', 'session.status']
  }
};
```

**3. Arrancar OpenWA:**

Usa el script PowerShell incluido:

```powershell
.\openwa-quickstart.ps1
```

O manualmente:

```powershell
wa-automate --config openwa-config.js
```

**4. Configurar Laravel para conectar al host:**

En `.env`, usa `host.docker.internal`:

```env
OPENWA_BASE_URL=http://host.docker.internal:3000
OPENWA_API_KEY=your-api-key-here
```

**5. Reiniciar contenedor PHP:**

```bash
docker restart php_gestor
```

**6. Escanear QR:**

OpenWA abrirá una ventana emergente con el QR, o accede a:

```
http://localhost:3000
```

Escanea con WhatsApp (mismo proceso que Opción A).

**7. Validar conexión:**

```bash
make openwa-validate
```

---

### Opción C: OpenWA en Linux/macOS (Desarrollo local)

Si tu entorno de desarrollo es Linux o macOS.

**1. Instalar OpenWA:**

```bash
npm install -g @open-wa/wa-automate
```

**2. Usar el script incluido:**

```bash
chmod +x openwa-quickstart.sh
./openwa-quickstart.sh
```

Este script:
- Verifica Node.js
- Instala dependencias
- Inicia OpenWA en puerto 3000
- Configura webhook automáticamente

**3. Configurar Laravel:**

```env
OPENWA_BASE_URL=http://localhost:3000  # Si no usas Docker
# o http://host.docker.internal:3000 si Laravel está en Docker
```

**4. Escanear QR y validar:**

Mismo proceso que opciones anteriores.

---

### Post-instalación: Verificación completa

Una vez que OpenWA esté instalado y conectado:

**1. Comprobar estado de sesión:**

```bash
docker exec php_gestor php artisan tinker
```

```php
$client = new \App\Services\OpenWA\OpenWAClient();
$session = $client->getSession();
dd($session);
// Debe mostrar: ["status" => "CONNECTED", "isConnected" => true, ...]
```

**2. Enviar mensaje de prueba:**

```bash
docker exec php_gestor php artisan tinker
```

```php
$notifier = app(\App\Services\WhatsApp\WhatsappNotificationService::class);
$notifier->sendToPhone('612345678', 'Mensaje de prueba desde Laravel');
// Cambia '612345678' por tu número de prueba
```

**3. Verificar que el mensaje se guardó en BD:**

```bash
docker exec php_gestor php artisan tinker
```

```php
\App\Models\WhatsappMessage::latest()->first();
// Debe mostrar el mensaje recién enviado con status 'pending' o 'sent'
```

**4. Comprobar que el worker de colas procesa mensajes:**

```bash
make queue-heartbeat
# Debe mostrar: ✔ Worker activo

make logs-queue
# Ver logs del worker procesando SendWhatsappMessageJob
```

**5. Verificar webhook (envía un mensaje desde WhatsApp):**

Envía un mensaje desde tu móvil al número conectado a OpenWA.

```bash
make logs-app
# Debe mostrar: "OpenWA webhook received: message.received"
```

Comprueba que se guardó en BD:

```bash
docker exec php_gestor php artisan tinker
```

```php
\App\Models\WhatsappMessage::inbound()->latest()->first();
// Debe mostrar el mensaje entrante
```

---

### Troubleshooting OpenWA

#### OpenWA no conecta

**Síntoma:** El QR no aparece o OpenWA se queda en "Connecting..."

**Solución:**
```bash
# Ver logs de OpenWA
docker logs -f openwa-gateway  # Si usas Docker
# o revisar la terminal donde corre OpenWA en local

# Reiniciar OpenWA
make down-openwa && make up-openwa  # Docker
# o Ctrl+C y relanzar en local
```

#### Laravel no puede conectar a OpenWA

**Síntoma:** Error "Connection refused" o "Could not resolve host"

**Solución:**
```bash
# Desde dentro del contenedor PHP, hacer ping
docker exec php_gestor ping openwa  # Si OpenWA está en Docker
docker exec php_gestor ping host.docker.internal  # Si OpenWA está en host

# Verificar que OPENWA_BASE_URL es correcto en .env
docker exec php_gestor php artisan config:clear
docker exec php_gestor php artisan config:cache
```

#### Webhook no recibe eventos

**Síntoma:** OpenWA conecta, mensajes se envían, pero no llegan webhooks

**Solución:**
```bash
# 1. Verificar que la URL del webhook es accesible desde OpenWA
curl http://localhost:8077/api/webhooks/openwa
# Debe devolver 405 Method Not Allowed (es correcto, solo acepta POST)

# 2. Re-registrar webhook
docker exec php_gestor php artisan openwa:register-webhook

# 3. Ver logs de Laravel
make logs-app | grep -i webhook
```

#### Error "Invalid HMAC signature"

**Síntoma:** Webhooks son rechazados con 403

**Solución:**
```env
# Asegúrate de que OPENWA_WEBHOOK_SECRET coincide en .env y en la config de OpenWA
OPENWA_WEBHOOK_SECRET=mismo-secreto-en-ambos-lados
```

```bash
docker exec php_gestor php artisan config:clear
docker restart php_gestor
```

#### Mensajes se quedan en "pending"

**Síntoma:** Mensajes se crean en BD pero no se envían

**Solución:**
```bash
# 1. Verificar que el worker de colas está activo
make queue-heartbeat

# 2. Ver jobs fallidos
make queue-failed

# 3. Ver logs del worker
make logs-queue

# 4. Reintentar jobs fallidos
make queue-retry
```

#### Sesión se desconecta frecuentemente

**Síntoma:** Cada pocas horas OpenWA pide escanear QR de nuevo

**Solución:**
- Asegúrate de que el volumen de sesiones está persistiendo:
  ```bash
  docker volume ls | grep openwa
  ```
- No uses el mismo número en múltiples instancias de OpenWA simultáneamente
- Verifica que tu número de WhatsApp no está siendo bloqueado por WhatsApp

---

### Comandos útiles OpenWA

```bash
# Validar configuración completa
make openwa-validate

# Depurar resolución de teléfono para un usuario
make openwa-debug-phone USER_ID=123

# Ver logs específicos de OpenWA
make logs-openwa

# Reiniciar solo OpenWA (Docker)
docker restart openwa-gateway

# Limpiar sesión y reconectar (requiere escanear QR de nuevo)
docker volume rm gestor-de-usuarios_openwa-sessions
make up-openwa
```

------

## ✅ Verificación post-instalación

### 1. Comprobar que los contenedores están activos

```bash
make ps
# o: docker compose ps
```

Todos los servicios deben estar en estado `Up`.

### 2. Verificar logs

```bash
make logs-app
# o: docker exec php_gestor tail -n 50 storage/logs/laravel.log
```

No debe haber errores críticos.

### 3. Verificar worker de colas

```bash
make queue-heartbeat
# Debe mostrar: ✔ Worker activo
```

### 4. Verificar OpenWA (si aplica)

```bash
make openwa-validate
```

### 5. Acceder a la aplicación

Abre tu navegador en:
- **Con Docker:** `http://localhost:8077`
- **Sin Docker:** `http://localhost:8000`

Deberías ver la pantalla de login o la página principal.

### 6. Ejecutar smoke test completo

```bash
make smoke-test
```

Esto ejecuta todas las validaciones automáticas post-deploy.

---

## 🛠️ Comandos útiles post-instalación

### Ver todos los comandos disponibles

```bash
make help
```

### Desarrollo diario

```bash
make dev              # Arranca entorno completo (serve + queue + vite + logs)
make logs-app         # Ver logs de Laravel
make tinker           # REPL interactivo de Laravel
make cache-clear      # Limpiar cachés
```

### Gestión de colas

```bash
make queue-failed     # Ver jobs fallidos
make queue-retry      # Reintentar todos
make queue-flush      # Limpiar jobs fallidos
```

### Testing

```bash
make test             # Ejecutar todos los tests
make test-openwa      # Solo tests de OpenWA
```

### Comandos personalizados

```bash
make sync-users           # Sincronizar usuarios DB10 → DB11
make openwa-debug-phone USER_ID=123  # Depurar teléfono de usuario
```

---

## 🐛 Troubleshooting

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Causa:** Laravel no puede conectarse a MySQL.

**Solución:**
- Si usas Docker, verifica que el servicio `db` está activo: `make ps`
- Comprueba que `DB_HOST` en `.env` es `db` (en Docker) o `127.0.0.1` (en local)
- Verifica credenciales en `.env`

### Error: "No application encryption key has been specified"

**Causa:** `APP_KEY` no está generado en `.env`.

**Solución:**
```bash
make key-generate
# o: docker exec php_gestor php artisan key:generate --force
```

### Los jobs de cola no se procesan

**Causa:** El worker de colas no está corriendo.

**Solución:**
```bash
make queue-heartbeat  # Verificar estado
make restart          # Reiniciar contenedores
```

### Assets frontend no se compilan

**Causa:** El contenedor `node` falló o no terminó la compilación.

**Solución:**
```bash
make logs -f node     # Ver logs del contenedor
make npm-build        # Forzar rebuild
```

### OpenWA no recibe mensajes

**Causa:** Webhook no registrado o URL incorrecta.

**Solución:**
```bash
make openwa-validate               # Validar config
docker exec php_gestor php artisan openwa:register-webhook
```

### Permisos de escritura en `storage/`

**Causa:** El contenedor PHP no tiene permisos.

**Solución:**
```bash
docker exec php_gestor chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec php_gestor chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### Comando artisan personalizado

Si necesitas ejecutar comandos artisan no incluidos en el Makefile:

```bash
make artisan CMD="route:list"
# o: docker exec php_gestor php artisan route:list
```

---

## 🔐 Configuración de producción

Antes de desplegar en producción, asegúrate de:

### 1. Variables de entorno

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning

# Usar Redis para mejor rendimiento
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

### 2. Optimizaciones

```bash
make cache  # Generar cachés de config, rutas y vistas
```

### 3. Seguridad

- Configura `OPENWA_WEBHOOK_SECRET` para validación HMAC
- Usa HTTPS (configura SSL en `docker/nginx/ssl/`)
- Revisa permisos de archivos
- Activa rate limiting en rutas críticas

### 4. Monitoreo

- Configura logs externos (Sentry, Papertrail, etc.)
- Monitorea el heartbeat del worker: `make queue-heartbeat`
- Revisa `make queue-failed` periódicamente

### 5. Backup

- Base de datos principal (`gestoria`)
- Archivos en `storage/app/`
- Configuración `.env`

---

## 📚 Documentación adicional

- **Visión general:** `README.md`
- **Arquitectura técnica:** `docs/PROJECT_DOCUMENTATION.md`
- **Operación diaria:** `RUNBOOK.md`
- **Reglas OpenWA:** `AGENTS.md`, `OPENWA_ARCHITECTURE.md`, `OPENWA_README.md`
- **API:** `docs/openapi.yaml` o `/api/docs` (en la app)

---

## 🆘 Soporte

Si encuentras problemas no cubiertos en esta guía:

1. Revisa los logs: `make logs-app`
2. Consulta el RUNBOOK: `RUNBOOK.md` (sección troubleshooting)
3. Ejecuta validaciones: `make smoke-test`
4. Revisa la documentación técnica en `docs/`

---

**¡Instalación completada!** 🎉

Ahora puedes empezar a desarrollar o desplegar en producción.

