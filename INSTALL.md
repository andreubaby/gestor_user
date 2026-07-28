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


## 🔌 OpenWA: Instalación y configuración

OpenWA es el gateway de WhatsApp integrado opcionalmente en el proyecto. Está comentado por defecto en `docker-compose.yml`.

### Requisito previo: Configurar variables de entorno

Asegúrate de tener estas variables en tu `.env`:

```env
# URL del servidor OpenWA
OPENWA_BASE_URL=http://openwa_gestor:3000

# Credenciales
OPENWA_API_KEY=your-api-key-here
OPENWA_SESSION_ID=default
OPENWA_WEBHOOK_SECRET=your-webhook-secret-here

# Configuración adicional
OPENWA_REQUEST_TIMEOUT=30000
OPENWA_DEFAULT_COUNTRY_CODE=34
OPENWA_PORT=3000

# Rate limiting y deduplicación
OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS=86400
OPENWA_WEBHOOK_THROTTLE_PER_MINUTE=120
```

---

### Instalación (3 pasos)

**1. Clonar el repositorio OpenWA:**

```bash
make openwa-clone
```

Este comando clona https://github.com/rmyndharis/OpenWA.git en el directorio del proyecto.

**2. Habilitar el servicio en docker-compose.yml:**

```bash
make openwa-enable
```

Esto descomenta el servicio `openwa` en `docker-compose.yml` para que se inicie junto con los demás servicios.

**3. Levantar los servicios:**

```bash
make up
# o si ya están corriendo:
make restart
```

**4. Escanear QR para conectar WhatsApp:**

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

**5. Validar configuración:**

```bash
make openwa-validate
```

Debe mostrar:
```
✓ OPENWA_BASE_URL configurado
✓ OPENWA_API_KEY configurado
✓ Conexión a OpenWA exitosa
✓ Sesión CONNECTED
```

---

### Comandos útiles

```bash
# Gestión
make openwa-status         # Ver estado de OpenWA
make openwa-logs           # Ver logs en tiempo real
make openwa-validate       # Validar configuración
make openwa-disable        # Deshabilitar servicio (comentar en docker-compose)

# Debugging
make openwa-debug-phone USER_ID=123  # Depurar teléfono de usuario

# Desarrollo avanzado
make openwa-clone          # Clonar/actualizar repositorio del gateway
```

---

### Deshabilitar OpenWA

Si no necesitas WhatsApp:

```bash
make openwa-disable
make restart
```

Esto comenta el servicio en `docker-compose.yml` y lo detiene.

---
---

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


