# 🖥️ Guía de instalación completa en XAMPP (Windows)

Todo lo necesario para ejecutar **Gestor de Usuarios Babyplant** en local con XAMPP, incluyendo las 11 bases de datos.

---

## 📋 Requisitos previos

| Herramienta | Versión mínima | Descarga |
|-------------|---------------|----------|
| XAMPP | 8.2+ (PHP 8.2) | https://www.apachefriends.org |
| Composer | 2.x | https://getcomposer.org |
| Node.js + npm | 18+ | https://nodejs.org |

---

## 1️⃣ Copiar el proyecto

Copia (o clona) la carpeta del proyecto dentro de htdocs:

```
C:\xampp\htdocs\gestor_usuarios\
```

---

## 2️⃣ Habilitar extensiones PHP

Abre `C:\xampp\php\php.ini` y asegúrate de que las siguientes líneas estén **sin** el `;` delante:

```ini
extension=pdo_mysql
extension=mysqli
extension=zip
extension=fileinfo
extension=openssl
extension=mbstring
extension=gd
extension=curl
extension=intl
```

> ⚠️ Reinicia Apache desde el panel de XAMPP después de guardar.

---

## 3️⃣ Habilitar mod_rewrite

En `C:\xampp\apache\conf\httpd.conf` verifica que esta línea **no tenga `#`**:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Y busca el bloque `<Directory "C:/xampp/htdocs">` y comprueba que tenga:

```apache
AllowOverride All
```

> ⚠️ Reinicia Apache si tuviste que hacer cambios.

---

## 4️⃣ Configurar Virtual Host

### 4.1 — Activar vhosts en Apache

En `C:\xampp\apache\conf\httpd.conf` asegúrate de que esta línea **no tenga `#`**:

```apache
Include conf/extra/httpd-vhosts.conf
```

### 4.2 — Añadir el VirtualHost

Edita `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y añade al **final**:

```apache
<VirtualHost *:80>
    ServerName gestor.local
    DocumentRoot "C:/xampp/htdocs/gestor_usuarios/public"
    <Directory "C:/xampp/htdocs/gestor_usuarios/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4.3 — Registrar el dominio local

Abre **como Administrador** el archivo:
```
C:\Windows\System32\drivers\etc\hosts
```

Añade al final:
```
127.0.0.1   gestor.local
```

> ⚠️ Reinicia Apache después de estos cambios.

---

## 5️⃣ Crear todas las bases de datos en phpMyAdmin

Accede a `http://localhost/phpmyadmin` y en la pestaña **SQL** ejecuta este bloque completo de una vez:

```sql
CREATE DATABASE IF NOT EXISTS gestoria     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS camioneros   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `default`   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS pluton       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS buscador     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS cronos       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS store        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS zeus         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS semillas     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS trabajadores CHARACTER SET latin1 COLLATE latin1_swedish_ci;
CREATE DATABASE IF NOT EXISTS fichajes     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5.1 — Crear usuario MySQL único para todas las BDs

```sql
CREATE USER IF NOT EXISTS 'gestoria'@'localhost' IDENTIFIED BY '1234';

GRANT ALL PRIVILEGES ON gestoria.*      TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON camioneros.*    TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON `default`.*    TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON pluton.*        TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON buscador.*      TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON cronos.*        TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON store.*         TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON zeus.*          TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON semillas.*      TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON trabajadores.*  TO 'gestoria'@'localhost';
GRANT ALL PRIVILEGES ON fichajes.*      TO 'gestoria'@'localhost';

FLUSH PRIVILEGES;
```

### 5.2 — Estructura mínima de la BD de fichajes (`fichar`)

Selecciona la BD `fichajes` en phpMyAdmin → pestaña **SQL** y ejecuta:

```sql
CREATE TABLE IF NOT EXISTS `users` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(255) NOT NULL,
    `email`             VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `password`          VARCHAR(255) NOT NULL,
    `work_mode`         VARCHAR(50)  NOT NULL DEFAULT 'office',
    `remember_token`    VARCHAR(100) NULL,
    `created_at`        TIMESTAMP NULL DEFAULT NULL,
    `updated_at`        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `punches` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `type`        VARCHAR(10)  NOT NULL COMMENT 'in | out',
    `mood`        TINYINT      NULL DEFAULT NULL,
    `happened_at` DATETIME     NOT NULL,
    `is_manual`   TINYINT(1)   NOT NULL DEFAULT 0,
    `note`        TEXT         NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `punches_user_id_happened_at_index` (`user_id`, `happened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_summaries` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `work_date`      DATE NOT NULL,
    `worked_minutes` INT  NOT NULL DEFAULT 0,
    `created_at`     TIMESTAMP NULL DEFAULT NULL,
    `updated_at`     TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `daily_summaries_user_id_work_date_unique` (`user_id`, `work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.3 — Estructura mínima de Polifonía (`polifonia`)

Selecciona la BD `polifonia` en phpMyAdmin → pestaña **SQL** y ejecuta:

```sql
CREATE TABLE IF NOT EXISTS `trabajadores` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) NULL,
    `activo`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> 💡 Las BDs pluton, buscador, cronos, store, zeus, semillas y trabajadores pueden dejarse vacías si no tienes los datos reales. La app controla los errores de conexión y simplemente no muestra datos de esas BDs.

---

## 6️⃣ Configurar el archivo `.env`

En la raíz del proyecto, si no existe `.env`, créalo copiando el ejemplo:

```
copy .env.example .env
```

Luego edita `.env` con estos valores para XAMPP local (sustituye todo el bloque de BDs):

```dotenv
APP_NAME="Gestor Usuarios"
APP_ENV=local
APP_KEY=                        # se genera en el paso 8
APP_DEBUG=true
APP_URL=http://gestor.local

LOG_CHANNEL=stack
LOG_LEVEL=debug

# ── BD principal del gestor ──────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestoria
DB_USERNAME=gestoria
DB_PASSWORD=1234

# ── BD Polifonía (trabajadores) ──────────────────────────────
DB3_HOST=127.0.0.1
DB3_PORT=3306
DB3_DATABASE=polifonia
DB3_USERNAME=gestoria
DB3_PASSWORD=1234

# ── BD Plutón ────────────────────────────────────────────────
DB4_HOST=127.0.0.1
DB4_PORT=3306
DB4_DATABASE=pluton
DB4_USERNAME=gestoria
DB4_PASSWORD=1234

# ── BD Buscador ──────────────────────────────────────────────
DB5_HOST=127.0.0.1
DB5_PORT=3306
DB5_DATABASE=buscador
DB5_USERNAME=gestoria
DB5_PASSWORD=1234

# ── BD Cronos ────────────────────────────────────────────────
DB6_HOST=127.0.0.1
DB6_PORT=3306
DB6_DATABASE=cronos
DB6_USERNAME=gestoria
DB6_PASSWORD=1234

# ── BD Store ─────────────────────────────────────────────────
DB7_HOST=127.0.0.1
DB7_PORT=3306
DB7_DATABASE=store
DB7_USERNAME=gestoria
DB7_PASSWORD=1234

# ── BD Zeus ──────────────────────────────────────────────────
DB8_HOST=127.0.0.1
DB8_PORT=3306
DB8_DATABASE=zeus
DB8_USERNAME=gestoria
DB8_PASSWORD=1234

# ── BD Semillas ──────────────────────────────────────────────
DB9_HOST=127.0.0.1
DB9_PORT=3306
DB9_DATABASE=semillas
DB9_USERNAME=gestoria
DB9_PASSWORD=1234

# ── BD Trabajadores (legacy, latin1) ─────────────────────────
DB10_HOST=127.0.0.1
DB10_PORT=3306
DB10_DATABASE=trabajadores
DB10_USERNAME=gestoria
DB10_PASSWORD=1234

# ── BD Fichajes ──────────────────────────────────────────────
DB11_HOST=127.0.0.1
DB11_PORT=3306
DB11_DATABASE=fichar
DB11_USERNAME=gestoria
DB11_PASSWORD=1234

# ── Sesión y Caché ───────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

# ── Mail (en local solo escribe en el log) ───────────────────
MAIL_MAILER=log
```

---

## 7️⃣ Instalar dependencias

Abre una terminal **en la carpeta del proyecto** (`C:\xampp\htdocs\gestor_usuarios`) y ejecuta:

```bash
# Dependencias PHP
composer install

# Dependencias JS y compilación de assets
npm install
npm run build
```

---

## 8️⃣ Preparar Laravel

```bash
# 1. Genera la clave de la app (OBLIGATORIO la primera vez)
php artisan key:generate

# 2. Limpiar toda la caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 3. Ejecutar migraciones (crea tablas en la BD principal: usuarios, sesiones, caché, etc.)
php artisan migrate

# 4. Enlace simbólico para storage
php artisan storage:link
```

---

## 9️⃣ Crear el primer usuario administrador

```bash
php artisan tinker
```

Dentro de tinker, pega esto y pulsa Enter:

```php
\App\Models\User::create([
    'name'     => 'Admin',
    'email'    => 'admin@gestor.local',
    'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
]);
exit
```

---

## ✅ Acceder a la aplicación

Una vez completados todos los pasos, abre el navegador en:

```
http://gestor.local
```

Inicia sesión con `admin@gestor.local` / `admin1234` (o las credenciales que hayas puesto).

---

## 📤 Importar datos de producción (opcional)

Si tienes volcados `.sql` de los servidores de producción, impórtalos desde phpMyAdmin:

1. Selecciona la BD correspondiente en el panel izquierdo
2. Pestaña **Importar** → selecciona el fichero `.sql` → **Continuar**

| Variable `.env` | BD local       | Qué contiene                |
|-----------------|----------------|-----------------------------|
| DB2             | `camioneros`   | BD camioneros               |
| DB3             | `default`      | Trabajadores Polifonía      |
| DB4             | `pluton`       | Usuarios Plutón             |
| DB5             | `buscador`     | Usuarios y workers Buscador |
| DB6             | `cronos`       | Usuarios Cronos             |
| DB7             | `store`        | Usuarios Store              |
| DB8             | `zeus`         | Usuarios Zeus               |
| DB9             | `semillas`     | Usuarios Semillas           |
| DB10            | `trabajadores` | Fichajes legacy             |
| DB11            | `fichajes`     | Usuarios y punches fichajes |

---

## 🔄 Comandos útiles del día a día

```bash
# Limpiar toda la caché de una vez
php artisan optimize:clear

# Ver logs en tiempo real
php artisan pail

# Ejecutar migraciones nuevas
php artisan migrate

# Recompilar assets tras cambios JS/CSS
npm run build

# Ver todas las rutas registradas
php artisan route:list
```

---

## 🐛 Solución de problemas frecuentes

| Problema | Solución |
|----------|----------|
| Error 403 Forbidden | Verifica `mod_rewrite` activo, `AllowOverride All` en el VirtualHost y que `Include conf/extra/httpd-vhosts.conf` no esté comentado |
| Error 500 | Revisa `storage/logs/laravel.log` para el detalle |
| "No application encryption key" | Ejecuta `php artisan key:generate` |
| Página en blanco | Activa `APP_DEBUG=true` en `.env` temporalmente |
| Error de conexión a BD | Verifica que MySQL de XAMPP esté corriendo y las credenciales en `.env` |
| Extensión zip no encontrada | Activa `extension=zip` en `php.ini` y reinicia Apache |
| Assets CSS/JS no cargan | Ejecuta `npm run build` y luego `php artisan optimize:clear` |
| "SQLSTATE: Access denied" | Revisa usuario/contraseña de la BD afectada en el `.env` |
| El módulo de fichajes no borra | Asegúrate de que la tabla `punches` existe en la BD `fichajes` (paso 5.2) |
| "Table 'fichajes.punches' doesn't exist" | Ejecuta el SQL del paso 5.2 en phpMyAdmin |
