# 🖥️ Guía de instalación en XAMPP

Guía completa para ejecutar **Gestor de Usuarios** en XAMPP (Windows).

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

> ⚠️ Reinicia Apache si tuviste que activarla.

---

## 4️⃣ Configurar Virtual Host

### 4.1 — Añadir el VirtualHost

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

### 4.2 — Registrar el dominio local

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

## 5️⃣ Crear la base de datos principal en phpMyAdmin

Accede a `http://localhost/phpmyadmin` y ejecuta:

```sql
CREATE DATABASE gestoria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestoria'@'localhost' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON gestoria.* TO 'gestoria'@'localhost';
FLUSH PRIVILEGES;
```

> Las bases de datos DB2–DB11 son remotas y no necesitan configuración local.

---

## 6️⃣ Configurar el archivo `.env`

En la raíz del proyecto edita `.env` con estos valores:

```dotenv
APP_URL=http://gestor.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestoria
DB_USERNAME=gestoria
DB_PASSWORD=1234
```

> El resto de variables (DB2–DB11, MAIL, etc.) se dejan tal cual.

---

## 7️⃣ Instalar dependencias

Abre una terminal en `C:\xampp\htdocs\gestor_usuarios` y ejecuta:

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
# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Ejecutar migraciones (crea tablas de sesión, caché, jobs, etc.)
php artisan migrate

# Enlace simbólico para storage
php artisan storage:link
```

---

## 9️⃣ Verificar permisos de carpetas

Asegúrate de que estas carpetas tengan permisos de **escritura**:

```
storage/
bootstrap/cache/
```

En Windows normalmente no hace falta hacer nada, pero si hay errores de permisos, haz clic derecho → Propiedades → Seguridad y da control total al usuario actual.

---

## ✅ Acceder a la aplicación

Una vez completados todos los pasos, abre el navegador en:

```
http://gestor.local
```

---

## 🔄 Comandos útiles del día a día

```bash
# Limpiar toda la caché
php artisan optimize:clear

# Ver logs en tiempo real
php artisan pail

# Ejecutar migraciones nuevas
php artisan migrate

# Recompilar assets tras cambios JS/CSS
npm run build
```

---

## 🐛 Solución de problemas frecuentes

| Problema | Solución |
|----------|----------|
| Error 403 Forbidden | Verifica que `mod_rewrite` esté activo y `AllowOverride All` en el VirtualHost |
| Error 500 | Revisa `storage/logs/laravel.log` para el detalle |
| "No application encryption key" | Ejecuta `php artisan key:generate` |
| Página en blanco | Activa `APP_DEBUG=true` en `.env` temporalmente |
| Error de conexión a BD | Verifica que MySQL de XAMPP esté corriendo y las credenciales en `.env` |
| Extensión zip no encontrada | Activa `extension=zip` en `php.ini` y reinicia Apache |
| Assets CSS/JS no cargan | Ejecuta `npm run build` y luego `php artisan optimize:clear` |

