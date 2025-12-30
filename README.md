# Gestor de Usuarios Babyplant

Aplicación **Laravel** para la **gestión centralizada de usuarios y trabajadores** en el ecosistema Babyplant. Permite **vincular, editar y visualizar** registros de una misma persona repartidos en **múltiples aplicaciones y bases de datos**, manteniendo coherencia de emails, credenciales y datos clave.

---

## 🧩 Objetivo del proyecto

Unificar la gestión de usuarios que existen simultáneamente en distintos sistemas internos:

* Aplicación principal (usuarios)
* Polifonía (trabajadores)
* Plutón
* Buscador (usuarios y trabajadores)
* Cronos (fichajes)
* Semillas
* Store
* Zeus
* Fichajes / Bienestar

El sistema permite **ver y editar todos los perfiles asociados desde una única vista**, evitando duplicidades y errores humanos.

---

## 🏗️ Stack tecnológico

* **Backend**: Laravel
* **Frontend**: Blade + TailwindCSS + Alpine.js
* **Base de datos**: MySQL (múltiples conexiones)
* **Autenticación**: Laravel Auth
* **Exportaciones**: Excel (maatwebsite/excel), PDF (dompdf)

---

## 🗄️ Arquitectura de bases de datos

El proyecto trabaja con **varias conexiones MySQL**, definidas en `config/database.php`:

| Sistema            | Conexión          |
| ------------------ | ----------------- |
| Usuarios principal | `mysql`           |
| Polifonía          | `mysql_polifonia` |
| Plutón             | `mysql_pluton`    |
| Buscador           | `mysql_buscador`  |
| Cronos             | `mysql_cronos`    |
| Semillas           | `mysql_semillas`  |
| Store              | `mysql_store`     |
| Zeus               | `mysql_zeus`      |
| Fichajes           | `mysql_fichajes`  |

La tabla **`usuario_vinculados`** actúa como **nexo central** mediante un `uuid`.

---

## 🔗 Vinculación de usuarios

Cada persona puede existir en varios sistemas. El vínculo se gestiona mediante:

* `uuid` único
* IDs opcionales por sistema (`usuario_id`, `trabajador_id`, `user_cronos_id`, etc.)

El sistema permite:

* Crear vínculos manuales
* Editar vínculos existentes
* Detectar usuarios no vinculados
* Sugerir emails comunes

---

## 🧑‍💻 Vista de edición unificada

La vista **`usuarios/edit_unificado.blade.php`** es el núcleo visual del sistema.

Características:

* Carrusel interactivo (Alpine.js)
* Una tarjeta por aplicación
* Formularios independientes por sistema
* Navegación por teclado (← → ESC)
* Renderizado dinámico con `x-html`

Cada tarjeta carga un **partial Blade**, por ejemplo:

* `partials/form_usuario`
* `partials/form_trabajador`
* `partials/form_user_cronos`
* `partials/form_user_fichajes`

---

## ⏱️ Fichajes y bienestar

El sistema integra el módulo de **fichajes** con análisis de bienestar:

* Bienestar diario (1–4)
* Cálculo automático de la **media semanal**
* Visualización de las **últimas 4 semanas**
* Representación mediante iconos (caritas)
* Popup con historial completo al pulsar

Los datos se obtienen desde la tabla `fichar` del sistema Cronos/Fichajes.

---

## 🧾 Gestión de ausencias

Desde Polifonía se gestionan:

* Vacaciones (V)
* Permisos (P)
* Bajas (B)

Características:

* Cálculo por año natural o año de imputación
* Rangos consecutivos
* Arrastre automático de días consecutivos
* Exportación a PDF oficial

---

## 📤 Exportaciones

* **Excel**: listado de trabajadores Polifonía con filtros aplicados
* **PDF**: certificados de vacaciones, permisos y bajas

---

## 🔐 Seguridad

* CSRF activo
* Validaciones estrictas en formularios
* Contraseñas encriptadas con `Hash::make`
* Separación clara entre sistemas

---

## ⚙️ Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Configura en `.env` todas las conexiones MySQL necesarias.

---

## 🚀 Uso recomendado

1. Importar usuarios y trabajadores existentes
2. Vincular registros mediante UUID
3. Gestionar perfiles desde la vista unificada
4. Usar Cronos/Fichajes como fuente de bienestar

---

## 📌 Notas importantes

* El sistema **no obliga** a que todos los módulos existan
* El email es el principal fallback de identificación
* La arquitectura está preparada para añadir nuevos sistemas

---

## 🧠 Filosofía del proyecto

> *Una persona, múltiples sistemas, una única verdad operativa.*

Este gestor elimina fricción, errores y duplicidades en entornos con múltiples aplicaciones heredadas.

---

## 👨‍💻 Autor / Equipo

Proyecto interno Babyplant.

Desarrollado con foco en **robustez**, **claridad** y **escalabilidad**.

---

Si necesitas:

* README más técnico
* Diagrama de arquitectura
* Documentación por controlador
* Onboarding para nuevos devs

👉 dímelo y lo ampliamos.
