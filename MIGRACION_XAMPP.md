# Migración de Laravel Dockerizado a XAMPP

---

## 1. Instalar y preparar XAMPP

- Descarga e instala **XAMPP** desde [apachefriends.org](https://www.apachefriends.org)
- Asegúrate de tener habilitados los módulos: **Apache**, **MySQL** y **PHP**
- Verifica que la versión de PHP de XAMPP sea compatible con la definida en `composer.json`
- Inicia **Apache** y **MySQL** desde el panel de control de XAMPP

---

## 2. Copiar el proyecto

- Copia toda la carpeta del proyecto **excepto** `vendor/`, `node_modules/` y `docker/` dentro de:
  ```
  C:\xampp\htdocs\gestor_usuarios
  ```

---

## 3. Instalar dependencias PHP

Abre una terminal en `C:\xampp\htdocs\gestor_usuarios` y ejecuta:

```bash
composer install
```

---

## 4. Crear el archivo `.env`

Copia el archivo de ejemplo:

```bash
copy .env.example .env
```

Edita `.env` con los valores para XAMPP:

```env
APP_URL=http://localhost/gestor_usuarios/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=root
DB_PASSWORD=
```

> ⚠️ Revisa también las variables que estaban definidas en `docker-compose.yml` y traslada las que sean necesarias al `.env`.

---

## 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

---

## 6. Crear la base de datos

- Abre **phpMyAdmin** en `http://localhost/phpmyadmin`
- Crea una nueva base de datos con el mismo nombre que pusiste en `DB_DATABASE`

---

## 7. Migrar la base de datos

**Opción A** — Si usas las migraciones de Laravel:

```bash
php artisan migrate
```

**Opción B** — Si tienes un dump SQL exportado del entorno Docker:

- Importa el archivo `.sql` directamente desde phpMyAdmin

---

## 8. Permisos de carpetas

En Windows con XAMPP no suele ser necesario cambiar permisos, pero asegúrate de que estas carpetas sean **escribibles**:

```
storage/
bootstrap/cache/
```

---

## 9. Generar el enlace simbólico de storage

```bash
php artisan storage:link
```

---

## 10. Configurar Apache — Virtual Host (recomendado)

Edita `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y añade:

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

Añade la siguiente línea al archivo `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 gestor.local
```

Reinicia Apache desde el panel de XAMPP.

> Si **no** usas Virtual Host, accede directamente a `http://localhost/gestor_usuarios/public`.  
> Asegúrate de que el archivo `public/.htaccess` esté presente y que `mod_rewrite` esté habilitado en Apache (`httpd.conf`).

---

## 11. Construir y desplegar la app React (maria-app)

Entra en la carpeta `maria app/` y ejecuta:

```bash
npm install
npm run build
```

Copia el contenido de la carpeta `dist/` generada dentro de:

```
C:\xampp\htdocs\gestor_usuarios\public\maria-app\
```

Para que la SPA funcione correctamente (rutas del lado del cliente), añade un archivo `.htaccess` dentro de `public/maria-app/` con el siguiente contenido:

```apache
Options -MultiViews
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.html [QSA,L]
```

---

## 12. Compilar los assets del proyecto Laravel (Vite)

```bash
npm install
npm run build
```

---

## 13. Verificar la aplicación

- Accede a `http://gestor.local` (o `http://localhost/gestor_usuarios/public`)
- Si hay errores, revisa los logs en:
  ```
  storage/logs/laravel.log
  ```

---

## 🔧 Extensiones PHP a habilitar en `php.ini`

Abre `C:\xampp\php\php.ini`, busca cada línea con **Ctrl+F**, quita el `;` del principio y guarda. Luego reinicia Apache.

### Obligatorias

```ini
extension=mbstring        ; Laravel base
extension=openssl         ; Laravel base, cifrado, HTTPS
extension=pdo_mysql       ; Conexión a MySQL
extension=mysqli          ; Alternativa MySQL
extension=curl            ; HTTP requests, Guzzle
extension=fileinfo        ; Subida de ficheros, MIME types
extension=zip             ; ext-zip (requerido en composer.json)
extension=xml             ; League/CommonMark, DomPDF
extension=dom             ; DomPDF
extension=gd              ; Imágenes (maatwebsite/excel, dompdf)
extension=exif            ; Metadatos de imágenes
extension=intl            ; Internacionalización, Carbon
```

### Recomendadas

```ini
extension=bcmath          ; Operaciones numéricas precisas (Laravel)
extension=ctype           ; Validaciones internas de Laravel
extension=tokenizer       ; Requerido por Laravel
extension=xmlwriter       ; Exportación Excel (maatwebsite)
extension=simplexml       ; Exportación Excel (maatwebsite)
extension=iconv           ; Conversión de caracteres
```

### Verificar que están activas

Crea `C:\xampp\htdocs\phpinfo.php` con el siguiente contenido, ábrelo en el navegador y busca cada extensión. Bórralo cuando termines.

```php
<?php phpinfo();
```

---

## ⚠️ Equivalencias Docker → XAMPP

| Concepto            | Docker                          | XAMPP                                      |
|---------------------|---------------------------------|--------------------------------------------|
| Servidor web        | Nginx (`default.conf`)          | Apache (`httpd-vhosts.conf` + `.htaccess`) |
| PHP                 | Contenedor `php:9000`           | PHP integrado en XAMPP                     |
| Base de datos       | Contenedor MySQL/MariaDB        | MySQL de XAMPP (phpMyAdmin)                |
| Variables de entorno| `docker-compose.yml`            | Archivo `.env`                             |
| Timeouts            | Configurados en `nginx.conf`    | Configurar en `php.ini` y `httpd.conf`     |
| SPA maria-app       | `location ^~ /maria-app/`       | `.htaccess` dentro de `public/maria-app/`  |

