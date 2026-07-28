# Documentación técnica de traspaso - Gestor Usuarios

Fecha de referencia: 2026-07-28

Este documento resume la arquitectura, rutas, modelos, conexiones de base de datos, servicios, jobs e integraciones críticas del repositorio `gestor_usuarios`, para facilitar el traspaso del proyecto a otra persona.

---

## 1. Resumen ejecutivo

`gestor_usuarios` es un monolito en **Laravel 12** que centraliza la gestión de usuarios, trabajadores, fichajes, RRHH, automatizaciones y mensajería de WhatsApp mediante **OpenWA**.

El diseño del proyecto se apoya en dos ideas clave:

1. **Integración multi-base de datos**: el sistema lee y escribe en varias conexiones MySQL además de SQLite local.
2. **Vinculación por UUID**: la identidad entre sistemas se resuelve a través de `usuarios_vinculados`, evitando unir datos por coincidencias débiles como nombre o email cuando existe un vínculo explícito.

---

## 2. Stack y componentes principales

### Backend
- Laravel 12
- PHP-FPM
- Eloquent ORM
- Laravel Queue con driver `database`
- Scheduler de Laravel

### Frontend
- Blade
- Tailwind
- Alpine.js
- React + Vite para la SPA `maria app/` (TimeGuard Pro)

### Infraestructura
- Docker / Docker Compose
- Nginx
- MySQL múltiples conexiones
- SQLite local para desarrollo o fallback

### Integraciones externas
- OpenWA para WhatsApp
- Swagger / OpenAPI para documentación de API
- Exportación Excel y PDF

---

## 3. Arquitectura funcional

### Módulos principales

1. **Usuarios y vinculación entre sistemas**
   - Controlador principal: `app/Http/Controllers/UsuarioController.php`
   - Servicio de negocio: `app/Services/VinculacionService.php`
   - Modelo hub: `app/Models/UsuarioVinculado.php`

2. **Trabajadores y fichajes**
   - `app/Http/Controllers/TrabajadorController.php`
   - `app/Http/Controllers/FichajeController.php`
   - `app/Http/Controllers/FichajesDiariosController.php`
   - Servicios: `app/Services/FichajesService.php`, `app/Services/FichajesDiariosService.php`, `app/Services/BienestarService.php`

3. **TimeGuard Pro**
   - API REST en `app/Http/Controllers/TimeguardController.php`
   - Modelos: `TimeguardWorker`, `TimeguardTimeEntry`, `TimeguardCompensation`, `TimeguardAuditLog`

4. **WhatsApp / OpenWA**
   - Cliente HTTP: `app/Services/OpenWA/OpenWAClient.php`
   - Orquestador: `app/Services/WhatsApp/WhatsappNotificationService.php`
   - Webhook: `app/Http/Controllers/OpenWAWebhookController.php`
   - Mensajería automática: `app/Services/WhatsApp/AutomaticMessageChainService.php`

5. **Automatizaciones**
   - `app/Http/Controllers/AutomationSequenceController.php`
   - Modelos: `AutomationSequence`, `ScheduledAutomation`, `AutomationSequenceExecutionLog`
   - Jobs: `SendAutomaticMessageStepJob`

6. **RRHH y exportaciones**
   - `app/Http/Controllers/RrhhDocumentosController.php`
   - Exportación Excel: `GenerateFichajesExcelJob`

---

## 4. Rutas HTTP

## 4.1 `routes/web.php`

### Rutas públicas

- `/` → redirección a `/gestoria`
- `/automation/attachments/{filename}` → sirve adjuntos públicos para automatización
- `/maria-app/{any?}` → SPA React/Vite compilada

### Autenticación

- `GET /login` y `POST /login`
- `GET /register` y `POST /register`
- `GET /csrf-token`
- `POST /logout`

### Panel privado (`auth`)

#### Gestoría
- `GET /gestoria`

#### Trabajadores
- `GET /trabajadores/{id}/edit`
- `PUT /trabajadores/{id}`
- `GET /trabajadores/{trabajador}/fichajes`
- `POST /trabajadores/{id}/toggle-activo`
- `GET /trabajadores/export`
- `POST /trabajadores/{trabajador}/dias`
- `GET /trabajadores/{trabajador}/dias`
- `GET /trabajadores/{trabajador}/vacaciones/pdf`
- `GET /trabajadores/{trabajador}/permisos/pdf`
- `GET /trabajadores/{trabajador}/bajas/pdf`

#### Usuarios
- `GET /usuarios`
- `GET /usuarios/{id}/edit`
- `PUT /usuarios/{id}`
- `GET /usuarios/unificado/{email}`
- `GET /usuarios/unificado/uuid/{uuid}`
- `GET /usuarios/vincular`
- `POST /usuarios/vincular`
- `GET /usuarios/vincular/sugerencias`
- `GET /usuarios/vincular/{vinculo}/edit`
- `PUT /usuarios/vincular/{vinculo}`
- `POST /usuarios/bulk-actions`
- `GET /usuarios/export/excel`
- `GET /usuarios/{trabajador}/fichajes-unificado`
- `GET /usuarios/onboarding`
- `POST /usuarios/onboarding/send`

#### Sistemas externos
- Buscador:
  - `GET /buscador/user/{id}/edit`
  - `PUT /buscador/user/{id}`
  - `GET /buscador/worker/{id}/edit`
  - `PUT /buscador/worker/{id}`
- Cronos:
  - `GET /cronos/user/{id}/edit`
  - `PUT /cronos/user/{id}`
- Semillas:
  - `GET /semillas/user/{id}/edit`
  - `PUT /semillas/user/{id}`
- Store:
  - `GET /store/user/{id}/edit`
  - `PUT /store/user/{id}`
- Zeus:
  - `GET /zeus/user/{id}/edit`
  - `PUT /zeus/user/{id}`
- Plutón:
  - `GET /pluton/{pluton}/edit`
  - `PUT /pluton/{pluton}`
- Fichajes:
  - `GET /fichajes/users/create`
  - `POST /fichajes/users`
  - `GET /fichajes/users/{id}/edit`
  - `PUT /fichajes/users/{id}`

#### Fichajes diarios
- `GET /fichajes/diarios`
- `GET /fichajes-diarios/export`
- `GET /fichajes/{trabajador}/historial`
- `DELETE /fichajes/punches/{punch}`

#### Grupos y RRHH
- `GET /groups/asignar`
- `POST /groups/asignar`
- `DELETE /groups/asignar`
- `GET /rrhh/documentos`
- `POST /rrhh/documentos/pdf`
- `POST /rrhh/documentos/zip`
- `POST /rrhh/documentos/zip-formacion-todos`

#### Tacógrafo
- `resource /tacografo`
- `POST /tacografo/{tacografo}/toggle-activo`
- `POST /tacografo/{tacografo}/fecha`
- `GET /tacografo/create`
- `POST /tacografo`

#### OpenWA / WhatsApp
- `GET /openwa/colaboraciones`
- `GET /openwa/automatizaciones`
- `GET /openwa/colaboraciones/diagnostics`
- `GET /openwa/colaboraciones/recent-messages`
- `GET /openwa/api/search-trabajadores`
- `POST /openwa/colaboraciones/send-trabajador`
- `POST /openwa/colaboraciones/send-grupo`
- `POST /openwa/colaboraciones/send-grupo-openwa`
- `POST /openwa/colaboraciones/send-phone`
- `POST /openwa/colaboraciones/create-group`
- `POST /openwa/automatizaciones/enviar`

#### Automatizaciones
- `GET /automation/missing-punch/preview`
- `resource /automation/sequences`
- `POST /automation/api/upload-attachment`
- `GET /automation/attachments/private/{filename}`
- `GET /automation/api/search-trabajadores`
- `GET /automation/api/sequences-live-status`
- `POST /automation/sequences/{sequence}/execute`
- `POST /automation/sequences/bulk-actions`
- `GET /automation/sequences/bulk-actions/export-csv`
- `POST /automation/sequences/{sequence}/toggle-status`
- `POST /automation/sequences/{sequence}/duplicate`
- `POST /automation/sequences/{sequence}/save-template`
- `POST /automation/sequences/{sequence}/create-from-template`
- `GET /automation/sequences/{sequence}/schedule/create`
- `POST /automation/sequences/{sequence}/schedule`
- `GET /automation/sequences/{sequence}/schedule/{schedule}/edit`
- `PUT /automation/sequences/{sequence}/schedule/{schedule}`
- `DELETE /automation/sequences/{sequence}/schedule/{schedule}`
- `GET /automation/audit`
- `GET /automation/audit/export-csv`

### Archivos relevantes
- `routes/web.php`
- `app/Http/Controllers/*.php`

---

## 4.2 `routes/api.php`

### TimeGuard Pro API
Protegida por `throttle:timeguard-api`.

- `GET /api/timeguard/workers`
- `POST /api/timeguard/workers`
- `PUT /api/timeguard/workers/{id}`
- `DELETE /api/timeguard/workers/{id}`
- `GET /api/timeguard/entries`
- `POST /api/timeguard/entries`
- `PUT /api/timeguard/entries/{id}`
- `DELETE /api/timeguard/entries/{id}`
- `GET /api/timeguard/compensations`
- `POST /api/timeguard/compensations`
- `DELETE /api/timeguard/compensations/{id}`
- `POST /api/timeguard/import`

### OpenWA Webhooks
Sin CSRF y con rate limit `throttle:openwa-webhook`.

- `POST /api/webhooks/openwa`
- `POST /api/webhook/openwa` (alias legacy)

---

## 5. Controladores y responsabilidades

### `TimeguardController`
- CRUD de workers, time entries y compensations
- Import masivo desde localStorage
- Serialización JSON específica para la SPA

### `UsuarioController`
- Gestión principal de usuarios
- Edición unificada por email o UUID
- Vinculación entre sistemas
- Exportaciones y acciones masivas

### `TrabajadorController`
- Edición de trabajadores Polifonía
- Fichajes y cambio rápido de estado
- Consulta de fichajes

### `FichajeController`
- Edición de fichajes
- Historial
- Eliminación de punches individuales

### `FichajesDiariosController`
- Listado diario
- Exportación

### `OpenWAWebhookController`
- Recibe eventos OpenWA
- Verifica HMAC si está configurado
- Aplica idempotencia por evento
- Persiste mensajes y actualiza estados

### `OpenWACollaborationController`
- Gestión de la vista de colaboraciones
- Envío de mensajes a usuarios, grupos y teléfonos
- Búsquedas y diagnósticos

### `AutomationSequenceController`
- CRUD de secuencias automáticas
- Subida y servidor de adjuntos
- Ejecución, duplicado, plantillas, schedules y auditoría

### `RrhhDocumentosController`
- PDF y ZIP de documentos de RRHH

### `OnboardingController`
- Envío de onboarding

### `MissingPunchReminderController`
- Vista previa de recordatorios por fichajes faltantes

### `GroupAssignmentController`
- Asignación y desasignación de grupos

### `TacografoController`
- CRUD de tacógrafos
- Toggle de activo
- Fecha asociada

---

## 6. Servicios clave

### `app/Services/OpenWA/OpenWAClient.php`
Cliente HTTP de OpenWA.

Funciones principales:
- `sendText(string $phone, string $message)`
- `sendTextToChatId(string $chatId, string $message)`
- `sendFileToChatId(string $chatId, string $fileUrl, ?string $caption = null, ?string $filename = null)`
- `sendFile(string $phone, string $fileUrl, ?string $caption = null, ?string $filename = null)`
- `getSession()`
- `registerWebhook(string $url, array $events, ?string $secret = null)`
- `getSessionGroups()`

Detalles relevantes:
- Valida `base_url` y `api_key` en el constructor
- Usa `X-API-Key`
- Timeout configurable
- Soporta recuperación de sesión si OpenWA responde 404 o sesión inactiva
- Convierte teléfono a chat ID con `phoneToChatId()`

### `app/Services/WhatsApp/WhatsappNotificationService.php`
Orquestador de notificaciones.

Funciones principales:
- `sendWelcomeMessage(User $user)`
- `sendOtp(User $user, string $code)`
- `sendOrderUpdate(User $user, array $orderData)`
- `sendToUser(User $user, string $message, bool $async = true, ?string $resolvedPhone = null)`
- `sendToPhone(string $phone, string $message, ?int $userId = null, bool $async = true)`
- `sendToChatId(string $chatId, string $message, ?int $userId = null, bool $async = true)`
- `sendFileToChatId(...)`
- `sendFileToPhone(...)`
- `sendFileToGroup(...)`
- `sendToGroup(WhatsappGroup $group, string $message, bool $async = true)`

Puntos clave:
- El envío recomendado es asíncrono
- Crea registros en `WhatsappMessage` con estado `pending`
- Lanza `SendWhatsappMessageJob` o `SendWhatsappFileJob`
- Resuelve teléfono del usuario desde `usuarios_vinculados` y luego desde la BD remota correspondiente

### `app/Services/WhatsApp/AutomaticMessageChainService.php`
- Encola pasos de automatización con retraso acumulado
- Valida adjuntos y tipo de destino
- Registra trazas en `AutomationSequenceExecutionLog`
- Soporta destinos `person`, `local_group` y `openwa_group`

### `app/Services/VinculacionService.php`
- Limpia sesión de preselección
- Valida IDs externos contra cada conexión
- Guarda o actualiza `UsuarioVinculado`

### Otros servicios de negocio
- `FichajesService`
- `FichajesDiariosService`
- `BienestarService`
- `AusenciasService`
- `CatalogosService`
- `BulkUsuarioActionService`
- `UsuarioLookupService`
- `TrabajadoresIndexService`
- `MissingPunchReminderService`
- `AttachmentUrlValidatorService`

---

## 7. Jobs y ejecución asíncrona

La cola usa por defecto el driver `database`.

### Jobs relevantes
- `SendWhatsappMessageJob`
- `SendWhatsappFileJob`
- `SendAutomaticMessageStepJob`
- `GenerateFichajesExcelJob`

### Flujo esperado de WhatsApp outbound

```text
Controller / Service
  -> WhatsappNotificationService
  -> SendWhatsappMessageJob / SendWhatsappFileJob
  -> OpenWAClient
  -> OpenWA API
```

### Flujo esperado de automatizaciones

```text
AutomationSequenceController / AutomationSequence
  -> AutomaticMessageChainService
  -> SendAutomaticMessageStepJob
  -> OpenWAClient / services relacionados
```

### Configuración de cola
- `config/queue.php`
- `QUEUE_CONNECTION=database`
- tablas: `jobs`, `failed_jobs`

---

## 8. Modelo de datos y conexiones

## 8.1 Conexiones definidas en `config/database.php`

### Conexión por defecto
- `sqlite` si no se define `DB_CONNECTION`
- El proyecto también soporta `mysql` como conexión principal

### Conexiones multi-base relevantes

| Conexión | Uso principal |
|---|---|
| `mysql` | Base principal de Laravel |
| `mysql_polifonia` | Trabajadores Polifonía y tacógrafo |
| `mysql_pluton` | Usuarios Plutón y dispositivos |
| `mysql_buscador` | Usuarios y workers del buscador |
| `mysql_cronos` | Usuarios Cronos |
| `mysql_store` | Usuarios Store |
| `mysql_zeus` | Usuarios Zeus |
| `mysql_semillas` | Usuarios Semillas |
| `mysql_trabajadores` | Base de trabajadores / fichar |
| `mysql_fichajes` | Fichajes, daily summaries y modelos TimeGuard/fichajes |

### Observación importante
No asumir una sola base de datos. Muchos modelos declaran `protected $connection` y algunos flujos hacen consultas cruzadas entre conexiones.

---

## 8.2 Modelos principales

### Núcleo de identidad

#### `app/Models/Usuario.php`
- Tabla principal de usuarios locales
- Encripta contraseña al asignarla
- Campos: `nombre`, `email`, `password`

#### `app/Models/UsuarioVinculado.php`
- Tabla: `usuarios_vinculados`
- Es el hub de vinculación entre sistemas
- Campos principales:
  - `uuid`
  - `usuario_id`
  - `trabajador_id`
  - `pluton_id`
  - `user_buscador_id`
  - `worker_buscador_id`
  - `user_cronos_id`
  - `user_semillas_id`
  - `user_store_id`
  - `user_zeus_id`
  - `user_fichaje_id`

Relaciones:
- `usuario()` → `Usuario`
- `trabajador()` → `TrabajadorPolifonia`
- `pluton()` → `UserPluton`
- `cronos()` → `UserCronos`
- `semillas()` → `UserSemillas`
- `store()` → `UserStore`
- `zeus()` → `UserZeus`
- `userBuscador()` → `UserBuscador`
- `workerBuscador()` → `WorkerBuscador`

### Modelos externos por conexión

#### Polifonía
- `TrabajadorPolifonia`
- `Trabajador`
- `TrabajadorDia`
- `Tacografo`
- `UsuarioPolifonia`

#### Plutón
- `UserPluton`
- `UserDevice`

#### Buscador
- `UserBuscador`
- `WorkerBuscador`

#### Cronos
- `UserCronos`

#### Semillas
- `UserSemillas`

#### Store
- `UserStore`

#### Zeus
- `UserZeus`

#### Trabajadores / fichajes
- `UserTrabajador`
- `UserFichaje`
- `Fichar`
- `Punch`
- `Daily`

### WhatsApp / OpenWA
- `WhatsappMessage`
- `WhatsappGroup`
- `WhatsappGroupMember`

### TimeGuard
- `TimeguardWorker`
- `TimeguardTimeEntry`
- `TimeguardCompensation`
- `TimeguardAuditLog`

### Automatizaciones
- `AutomationSequence`
- `ScheduledAutomation`
- `AutomationSequenceExecutionLog`

---

## 8.3 Relaciones más importantes

### `WhatsappMessage`
- `user()` → `User`
- Scopes:
  - `inbound()`
  - `outbound()`
  - `forSession($sessionId)`
  - `forChat($chatId)`
  - `forUser($userId)`
- Helpers de estado:
  - `markAsSent()`
  - `markAsDelivered()`
  - `markAsRead()`
  - `markAsFailed($errorMessage)`

### `WhatsappGroup`
- `creator()` → `User`
- `members()` → `WhatsappGroupMember`
- `messages()` → `WhatsappMessage` por `chat_id`

### `WhatsappGroupMember`
- `group()` → `WhatsappGroup`

### `TimeguardWorker`
- `entries()` → `TimeguardTimeEntry`
- `compensations()` → `TimeguardCompensation`

### `TimeguardTimeEntry`
- `logs()` → `TimeguardAuditLog`

### `Daily`
- `user()` → `UserFichaje`

### `UserPluton`
- `devices()` → `UserDevice`

### `AutomationSequence`
- `templateSource()` → `AutomationSequence`
- `scheduledAutomations()` → `ScheduledAutomation`
- `executionLogs()` → `AutomationSequenceExecutionLog`
- `execute()` delega en `AutomaticMessageChainService`

### `ScheduledAutomation`
- `automationSequence()` → `AutomationSequence`
- Métodos de negocio:
  - `shouldRun()`
  - `markAsExecuted()`
  - `calculateNextExecution()`
  - `calculateNextExecutionFrom()`
  - `getEffectiveNextExecutionAttribute()`

---

## 9. Flujo crítico de WhatsApp / OpenWA

## 9.1 Envío outbound

Flujo esperado:

```text
Controller o servicio
  -> WhatsappNotificationService
  -> Job asíncrono
  -> OpenWAClient
  -> OpenWA API
```

Puntos importantes:
- No duplicar llamadas HTTP directas si ya existe `OpenWAClient`
- El formato de chat ID debe ser `{country_code}{number}@c.us`
- Reutilizar `OpenWAClient::phoneToChatId()` o la lógica equivalente del servicio

## 9.2 Recepción inbound y estados

Flujo esperado:

```text
OpenWA webhook
  -> OpenWAWebhookController::handle()
  -> handlers por tipo de evento
  -> persistencia/actualización de WhatsappMessage
```

Eventos manejados:
- `message.received`
- `session.status`
- `session.qr`
- `session.disconnected`
- `message.status`

### Idempotencia
- El webhook usa una clave derivada de `idempotencyKey`, `deliveryId` o hash del payload
- Se almacena en cache con TTL configurable
- Si el procesamiento falla, la clave se libera para permitir reintento

### HMAC
- Si `OPENWA_WEBHOOK_SECRET` está configurado, el webhook valida `X-HMAC-SHA256`
- Si no hay secreto, la validación se omite

### Estados de mensajes
- `pending`
- `sent`
- `delivered`
- `read`
- `failed`
- `received` para inbound

---

## 10. Configuración crítica

## 10.1 `config/openwa.php`

### Repositorio del gateway OpenWA

El proyecto no incluye el servidor OpenWA en este repositorio: se ejecuta como servicio externo (contenedor aparte) al que Laravel se conecta vía HTTP (`config('openwa.base_url')`).

- Repositorio usado por este proyecto para descargar/instalar el gateway: **https://github.com/rmyndharis/OpenWA**
- Clonar con:
```bash
git clone https://github.com/rmyndharis/OpenWA.git
```
- Repositorio base original (open-wa/wa-automate-nodejs) del que deriva: **https://github.com/open-wa/wa-automate-nodejs**
- Ver también `docker-compose.openwa.yml` y `OPENWA_README.md` para el setup del contenedor y las variables necesarias para levantarlo junto a este proyecto.

Variables principales:
- `OPENWA_BASE_URL`
- `OPENWA_API_KEY`
- `OPENWA_SESSION_ID`
- `OPENWA_WEBHOOK_SECRET`
- `OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS`
- `OPENWA_WEBHOOK_THROTTLE_PER_MINUTE`
- `OPENWA_REQUEST_TIMEOUT`
- `OPENWA_DEFAULT_COUNTRY_CODE`
- validación de adjuntos:
  - `OPENWA_ATTACHMENT_VALIDATION_ENABLED`
  - `OPENWA_ATTACHMENT_VALIDATION_TIMEOUT`
  - `OPENWA_ATTACHMENT_MAX_SIZE_BYTES`

## 10.2 Rate limiting
Definido en `app/Providers/AppServiceProvider.php`:
- `openwa-webhook`
- `timeguard-api`

## 10.3 Cola
- driver por defecto: `database`
- recomendado en local:

```bash
php artisan queue:work database --sleep=1 --tries=3 --backoff=60
```

---

## 11. Documentación existente útil

Archivos ya presentes y relevantes:
- `README.md`
- `RUNBOOK.md`
- `GOTCHAS.md`
- `OPENWA_ARCHITECTURE.md`
- `OPENWA_README.md`
- `docs/PROJECT_DOCUMENTATION.md`
- `docs/SWAGGER_USAGE.md`
- `docs/openapi.yaml`

Repositorio externo:
- OpenWA (gateway usado por el proyecto): **https://github.com/rmyndharis/OpenWA**
- OpenWA (proyecto base original): **https://github.com/open-wa/wa-automate-nodejs**

Este documento complementa esos materiales con un mapa de traspaso más operativo.

---

## 12. Puntos de riesgo y cosas que no conviene tocar sin revisar impacto

1. **No asumir una única base de datos**
   - Revisar la conexión del modelo antes de consultar o escribir.

2. **No romper la vinculación por UUID**
   - `usuarios_vinculados` es el hub principal.

3. **No debilitar OpenWA**
   - Mantener idempotencia, HMAC y uso de `OpenWAClient`.

4. **No escribir estados de WhatsApp de forma ad-hoc**
   - Usar los helpers de `WhatsappMessage`.

5. **No usar teléfonos en bruto como chat IDs**
   - El formato correcto es `@c.us`.

6. **No cambiar middleware del webhook sin revisar el flujo**
   - `routes/api.php` está diseñado para webhook público controlado por rate limit.

---

## 13. Lista corta de archivos para quien tome el proyecto

### Rutas
- `routes/web.php`
- `routes/api.php`

### Configuración
- `config/database.php`
- `config/openwa.php`
- `config/queue.php`
- `app/Providers/AppServiceProvider.php`

### Controladores
- `app/Http/Controllers/UsuarioController.php`
- `app/Http/Controllers/TrabajadorController.php`
- `app/Http/Controllers/TimeguardController.php`
- `app/Http/Controllers/OpenWAWebhookController.php`
- `app/Http/Controllers/OpenWACollaborationController.php`
- `app/Http/Controllers/AutomationSequenceController.php`
- `app/Http/Controllers/RrhhDocumentosController.php`

### Servicios
- `app/Services/OpenWA/OpenWAClient.php`
- `app/Services/WhatsApp/WhatsappNotificationService.php`
- `app/Services/WhatsApp/AutomaticMessageChainService.php`
- `app/Services/VinculacionService.php`

### Modelos
- `app/Models/Usuario.php`
- `app/Models/UsuarioVinculado.php`
- `app/Models/WhatsappMessage.php`
- `app/Models/AutomationSequence.php`
- `app/Models/ScheduledAutomation.php`
- `app/Models/TimeguardWorker.php`
- `app/Models/TimeguardTimeEntry.php`

### Webhook y pruebas
- `tests/Feature/Http/Controllers/OpenWAWebhookControllerTest.php`
- `tests/Unit/Jobs/SendWhatsappMessageJobTest.php`
- `tests/Unit/Services/OpenWA/`

---

## 14. Recomendación de operación diaria

### Levantar el proyecto
```bash
php artisan serve
```

### Worker de colas
```bash
php artisan queue:work database --sleep=1 --tries=3 --backoff=60
```

### Migraciones
```bash
php artisan migrate
```

### Pruebas
```bash
php artisan test
```

### Frontend
```bash
npm run dev
```

---

## 15. Conclusión

El sistema está organizado alrededor de tres ejes: **multi-base de datos**, **vinculación por UUID** y **mensajería OpenWA**. Si la persona que recibe el proyecto entiende esos tres puntos, podrá mantener y extender la aplicación con bastante seguridad.

Si se va a evolucionar el código, las zonas más sensibles son:
- `OpenWAClient`
- `OpenWAWebhookController`
- `WhatsappNotificationService`
- `VinculacionService`
- `UsuarioVinculado`
- `config/database.php`

---

## 16. Anexo: inventario de funciones por archivo

Esta sección detalla, archivo por archivo, los métodos públicos (y algunos protegidos relevantes) de controladores, servicios, jobs y modelos, para que quien reciba el proyecto pueda ubicar rápidamente qué hace cada pieza sin tener que leer todo el código.

### 16.1 Controladores (`app/Http/Controllers`)

**`AuthController.php`**
- `showLoginForm()`: muestra la vista de login.
- `refreshCsrfToken(Request $request)`: devuelve un token CSRF nuevo en JSON.
- `login(LoginRequest $request)`: autentica al usuario con throttling propio.
- `logout(Request $request)`: cierra sesión e invalida el token.
- `showRegisterForm()`: muestra la vista de registro.
- `register(Request $request)`: crea un nuevo usuario administrador.

**`UsuarioController.php`**
- `index(Request $request)`: listado principal de usuarios con filtros, orden y estadísticas.
- `edit($id)`: redirige a la edición unificada si el usuario ya tiene vínculo.
- `editUnificado($identificador)`: resuelve y muestra todos los datos de una persona por UUID o email a través de todos los sistemas.
- `editByUuid($uuid)`: alias de compatibilidad de `editUnificado`.
- `update(Request $request, $id)`: actualiza datos básicos del `Usuario` local.
- `vincular()`: muestra el formulario de vinculación limpiando preselecciones de sesión.
- `vincularStore(Request $request)`: valida y guarda un nuevo vínculo `UsuarioVinculado`.
- `vincularSuggestions(Request $request)`: sugiere coincidencias de vinculación por email/UUID en todos los sistemas.
- `vincularEdit(UsuarioVinculado $vinculo)`: formulario de edición de un vínculo existente.
- `vincularUpdate(Request $request, UsuarioVinculado $vinculo)`: actualiza un vínculo existente.
- `bulkActions(Request $request)`: activa, desactiva o auto-vincula usuarios en lote.
- `exportExcel(Request $request)`: exporta el listado de trabajadores/usuarios a Excel, incluyendo último fichaje.
- `showVinculacionManualConDatos($uuid)`: precarga el formulario de vinculación con los datos ya resueltos.
- `getDays(Request $r, int $trabajador)`: devuelve el payload de ausencias (vacaciones/permisos/bajas) por año.
- `storeDays(Request $r, int $trabajador)`: añade o elimina días de ausencia.
- `vacaciones/permisos/bajas(Request $request, $trabajadorId)`: generan el PDF correspondiente vía `AusenciasService`.

**`TrabajadorController.php`**
- `edit`, `update`: edición de datos del trabajador en Polifonía.
- `toggleActivo`: cambia el estado activo/inactivo rápido.
- `getFichajes`: devuelve fichajes asociados al trabajador.

**`FichajeController.php`**
- `edit(int $trabajadorId)`: formulario de edición de fichajes.
- `update(Request $request, int $trabajadorId)`: guarda cambios en fichajes.
- `getFichajes(Request $r, int $trabajadorId)`: historial de fichajes (modal).
- `fichajesUnificado(int $workerId)`: vista unificada de fichajes de un trabajador.
- `destroyPunch(Request $r, int $punchId)`: elimina un punch individual.

**`FichajesDiariosController.php`**
- `index(Request $request)`: listado diario de fichajes con filtros.
- `export(Request $request)`: exporta el listado diario a Excel.

**`TimeguardController.php`**
- `listWorkers/storeWorker/updateWorker/destroyWorker`: CRUD de trabajadores TimeGuard.
- `listEntries/storeEntry/updateEntry/destroyEntry`: CRUD de fichajes TimeGuard, con logs de auditoría embebidos.
- `listCompensations/storeCompensation/destroyCompensation`: CRUD de compensaciones.
- `import(Request $request)`: importación masiva transaccional desde `localStorage` del frontend.

**`OpenWAWebhookController.php`**
- `handle(Request $request)`: punto de entrada del webhook; valida HMAC, aplica idempotencia y despacha por tipo de evento.
- `handleMessageReceived`, `handleSessionStatus`, `handleSessionQr`, `handleSessionDisconnected`, `handleMessageStatus`, `handleUnknownEvent`: handlers protegidos por tipo de evento.
- `validateHmac(Request $request)`: valida la firma `X-HMAC-SHA256`.
- `isProcessed`/`markAsProcessed(string $eventId)`: control de idempotencia vía cache.

**`OpenWACollaborationController.php`**
- `index`: vista principal de colaboraciones.
- `automaticMessages`: vista de automatizaciones.
- `diagnostics`: diagnóstico de sesión OpenWA.
- `recentMessagesPartial`: fragmento con mensajes recientes.
- `searchTrabajadores`: búsqueda de trabajadores para destinatarios.
- `sendFromTrabajador`, `sendFromPhone`: envío de mensaje a un trabajador o teléfono.
- `sendToGroup`, `sendToOpenwaGroup`: envío a grupo local o grupo nativo de OpenWA.
- `sendAutomaticMessages`: dispara una cadena de mensajes automáticos.
- `createGroup`: crea un grupo local de WhatsApp.

**`AutomationSequenceController.php`**
- `index`: listado de secuencias.
- `create`/`store`: alta de una secuencia.
- `show`/`edit`/`update`/`destroy`: CRUD estándar de secuencias.
- `toggleStatus`: activa/desactiva una secuencia.
- `duplicate`: clona una secuencia.
- `saveAsTemplate`/`createFromTemplate`: gestión de plantillas.
- `createSchedule`/`storeSchedule`/`editSchedule`/`updateSchedule`/`destroySchedule`: CRUD de programaciones (`ScheduledAutomation`).
- `execute`: ejecuta manualmente una secuencia.
- `bulkActions`/`exportBulkActionsCsv`: acciones masivas y su exportación.
- `liveStatus`: estado en vivo para polling desde la UI.
- `audit`/`exportAuditCsv`: consulta y exportación del historial de ejecución.
- `searchTrabajadores`: búsqueda de destinatarios.
- `uploadAttachment`/`serveAttachment`: subida y servido de adjuntos.

**`RrhhDocumentosController.php`**
- `index`: vista de documentos RRHH.
- `pdf`: genera un PDF individual.
- `zip`: genera un ZIP de varios documentos.
- `zipFormacionTodos`: genera un ZIP masivo de formación para todos los trabajadores.

**`OnboardingController.php`**
- `onboardingCreate`: formulario de onboarding.
- `onboardingSend`: envía el mensaje/proceso de onboarding.

**`MissingPunchReminderController.php`**
- `index`: vista previa de recordatorios de fichajes faltantes, delega en `MissingPunchReminderService`.

**`GroupAssignmentController.php`**
- `create`/`store`/`detach`: asignación y desasignación de trabajadores a grupos.

**`TacografoController.php`**
- `index/create/store/edit/update/destroy`: CRUD estándar de tacógrafos.
- `toggle`: cambia el estado activo.
- `updateFecha`: actualiza la fecha asociada.

**Controladores de sistemas externos** (`UserBuscadorController`, `WorkerBuscadorController`, `UserCronosController`, `UserSemillasController`, `UserStoreController`, `UserZeusController`, `UserPlutonController`, `UserFichajeController`)
- Todos siguen el mismo patrón: `edit`/`update` (y en `UserFichajeController` además `create`/`store`) para gestionar el registro remoto correspondiente en su conexión MySQL dedicada.

---

### 16.2 Servicios (`app/Services`)

**`VinculacionService.php`**
- `clearPreselectionSession()`: limpia claves de sesión usadas para preselección de vinculación.
- `validateExternalIds(array $data)`: verifica que cada ID externo exista en su conexión/tabla correspondiente.
- `save(array $data)`: crea o actualiza el registro `UsuarioVinculado` por UUID.
- `storePreseleccionados(array $payload)`: guarda IDs preseleccionados en sesión.

**`UsuarioLookupService.php`**
- `resolveByIdentificador(string $identificador)`: resuelve todos los modelos relacionados (usuario, trabajador, plutón, etc.) a partir de UUID o email.
- `findVinculoFor(...)`: localiza el `UsuarioVinculado` asociado.

**`TrabajadoresIndexService.php`**
- `handle(Request $request)`: arma el listado paginado de usuarios/trabajadores con filtros, orden y estadísticas para `UsuarioController::index`.
- `buildCollection(?string $search)`: construye la colección base (sin paginar) usada también en la exportación Excel.

**`FichajesService.php`**
- `findUserByEmail(string $email)`: busca un `UserFichaje` por email.
- `getDailySummaries($userFichaje, ?string $from, ?string $to)`: obtiene resúmenes diarios de fichajes en un rango.

**`FichajesDiariosService.php`**
- `handle(Request $request)`: arma el listado diario con filtros.
- `getRowsForDate(...)`: obtiene filas para una fecha concreta.
- `exportExcel`/`generateExcelFile`: generan el archivo Excel de exportación.

**`BienestarService.php`**
- `attachMoodUltimasSemanas(...)`: añade indicadores de bienestar/mood recientes a una colección de trabajadores.
- `moodPorWorkerId(...)`: calcula el mood por trabajador desde `Fichar`.

**`AusenciasService.php`**
- `attachAusenciasPayload`/`buildAusenciasPayload`/`buildAusenciasPayloadByCalendarYear`: arman el payload de vacaciones/permisos/bajas por año.
- `storeDays(int $trabajador, array $data)`: añade o quita días de ausencia.
- `streamPdfVacaciones(...)`: genera y transmite el PDF de vacaciones/permisos/bajas.
- `resolvePdfDate(...)`: resuelve la fecha efectiva a imprimir en el PDF.

**`CatalogosService.php`**
- `getCatalogos()`: obtiene (y cachea) los catálogos usados en los formularios de vinculación.
- `invalidate()`: invalida la caché de catálogos.

**`BulkUsuarioActionService.php`**
- `setActivo`/`setActivoDetailed(array $workerIds, int $activo)`: activa/desactiva trabajadores en lote, con detalle de OK/omitidos/fallidos.
- `autoLinkByEmail(array $workerIds)`: intenta vincular automáticamente por coincidencia de email en todos los sistemas.

**`MissingPunchReminderService.php`**
- `sendForDate`/`previewForDate`: envía o previsualiza recordatorios de fichaje faltante para una fecha.
- `evaluateDate`: evalúa si corresponde enviar recordatorio ese día.
- `buildWorkersFromDailyRows`: arma la lista de trabajadores a partir de resúmenes diarios.
- `getOmittedEmails`/`getCampaignUserFichajeIds`/`getLinkedWorkers`: helpers de resolución de destinatarios.
- `isNonWorkingDay`/`isHolidayByConfiguredDates`/`isHolidayByTable`/`resolveWorkerWorkdays`: lógica de días laborables/festivos.

**`AttachmentUrlValidatorService.php`**
- `validate(string $url, int $stepNumber)`: valida que una URL de adjunto sea accesible, de tamaño y MIME permitidos antes de encolar el envío.

**`app/Services/OpenWA/OpenWAClient.php`**
- `sendText(string $phone, string $message)`: envía texto convirtiendo el teléfono a chat ID.
- `sendTextToChatId(string $chatId, string $message)`: envío directo por chat ID.
- `sendFileToChatId(...)`/`sendFile(...)`: envío de adjuntos, probando varios endpoints de compatibilidad.
- `getSession()`: consulta el estado de la sesión OpenWA.
- `registerWebhook(string $url, array $events, ?string $secret)`: registra el webhook en OpenWA.
- `getSessionGroups()`: lista los grupos de WhatsApp de la sesión.
- `phoneToChatId(string $phone)` *(protected)*: normaliza teléfono a formato `{código país}{número}@c.us`.
- `requestToSessionEndpoint`/`sendSessionRequest` *(protected)*: helpers de request HTTP con recuperación automática de sesión ante 404/"session not active".
- `resolveActiveSessionId`/`extractSessionsFromListResponse` *(protected)*: resuelven una sesión activa alternativa.
- `handleResponse`/`logRequest`/`logResponse` *(protected)*: manejo de respuestas y logging sin exponer secretos.

**`app/Services/WhatsApp/WhatsappNotificationService.php`**
- `sendWelcomeMessage(User $user)`: envía mensaje de bienvenida.
- `sendOtp(User $user, string $code)`: envía código OTP.
- `sendOrderUpdate(User $user, array $orderData)`: envía actualización de pedido.
- `sendToUser(...)`/`sendToPhone(...)`/`sendToChatId(...)`: envío genérico de texto, sync o async.
- `sendFileToChatId(...)`/`sendFileToPhone(...)`/`sendFileToGroup(...)`: envío de adjuntos a chat, teléfono o grupo.
- `sendToGroup(WhatsappGroup $group, string $message, bool $async = true)`: envío de texto a todos los miembros de un grupo.
- `resolvePhone(User $user)` *(protected)*: resuelve teléfono vía `UsuarioVinculado` → `UserTrabajador`/`TrabajadorPolifonia`.
- `phoneToChatId(string $phone)` *(protected)*: misma normalización que en `OpenWAClient`.
- `createPendingMessage(...)` *(protected)*: crea el registro `WhatsappMessage` en estado `pending` antes de encolar.

**`app/Services/WhatsApp/AutomaticMessageChainService.php`**
- `dispatchChain(array $steps, ?int $userId, ?string $automationName, ?int $automationSequenceId)`: valida y encola todos los pasos de una secuencia con delay acumulado, registrando log de ejecución.
- `assertValidStep` *(protected)*: valida tipo de destino, mensaje/adjuntos y existencia de destinatario.
- `resolveTargetLabel`/`resolvePersonLabel`/`resolveLocalGroupLabel`/`resolveOpenwaGroupLabel` *(protected)*: resuelven una etiqueta legible del destinatario para logs/UI.

---

### 16.3 Jobs (`app/Jobs`)

**`SendWhatsappMessageJob.php`**
- `handle()`: procesa el envío del mensaje pendiente (marca sent/failed según respuesta de OpenWA).
- `sendSavedMessage`/`sendNewMessage`: variantes según si el mensaje ya existe en BD o se crea en el momento.
- `failed(Throwable $exception)`: marca el mensaje como `failed` si el job agota reintentos.

**`SendWhatsappFileJob.php`**
- `handle()`: envía un adjunto (archivo/documento) vía `OpenWAClient`/`WhatsappNotificationService`.

**`SendAutomaticMessageStepJob.php`**
- `handle()`: ejecuta un paso individual de una secuencia automática (mensaje y/o adjuntos) hacia persona, grupo local o grupo OpenWA.
- `sendToPerson`/`sendToLocalGroup`/`sendToOpenwaGroup`: lógica específica según tipo de destino.
- `sendAttachmentsWithFallback`: intenta enviar adjuntos con reintentos/variantes de endpoint.
- `failed(Throwable $exception)`: registra el fallo.
- `recordExecutionLog(...)`: guarda el resultado en `AutomationSequenceExecutionLog`.

**`GenerateFichajesExcelJob.php`**
- `handle()`: genera el Excel de fichajes en background.
- `failed(Throwable $exception)`: maneja errores de generación.

---

### 16.4 Modelos (`app/Models`) — métodos de negocio y relaciones

**`UsuarioVinculado.php`**: relaciones `usuario()`, `trabajador()`, `pluton()`, `cronos()`, `semillas()`, `store()`, `zeus()`, `userBuscador()`, `workerBuscador()` — hub de vinculación entre sistemas.

**`WhatsappMessage.php`**: `user()`; scopes `inbound()`, `outbound()`, `forSession()`, `forChat()`, `forUser()`; helpers `markAsSent()`, `markAsDelivered()`, `markAsRead()`, `markAsFailed()`.

**`WhatsappGroup.php`**: `creator()`, `members()`, `messages()`.

**`WhatsappGroupMember.php`**: `group()`.

**`AutomationSequence.php`**: `templateSource()`, `scheduledAutomations()`, `executionLogs()`, `isActive()`, `execute(?int $userId)` (delega en `AutomaticMessageChainService::dispatchChain`).

**`AutomationSequenceExecutionLog.php`**: `automationSequence()`, `scheduledAutomation()`.

**`ScheduledAutomation.php`**: `automationSequence()`, `shouldRun()`, `markAsExecuted()`, `calculateNextExecution()`, `calculateNextExecutionFrom()`, `getEffectiveNextExecutionAttribute()`, `isActive()`.

**`TimeguardWorker.php`**: `entries()`, `compensations()`.

**`TimeguardTimeEntry.php`**: `logs()`.

**`UserPluton.php`**: `devices()`.

**`UserDevice.php`**: `user()`.

**`Daily.php`**: `user()`.

**`Usuario.php`**: `setPasswordAttribute()` (encripta password al asignarla).

**`UsuarioPolifonia.php`**: `getPasswordAttribute()` (desencripta password al leerla).

**Resto de modelos** (`TrabajadorPolifonia`, `Trabajador`, `TrabajadorDia`, `Tacografo`, `UserBuscador`, `WorkerBuscador`, `UserCronos`, `UserSemillas`, `UserStore`, `UserZeus`, `UserTrabajador`, `UserFichaje`, `Fichar`, `Punch`, `TimeguardCompensation`, `TimeguardAuditLog`): modelos Eloquent simples, principalmente definiendo `$connection`, `$table`, `$fillable` y en algunos casos relaciones puntuales (`UserFichaje::dailySummaries()`, `UserFichaje::punches()`).







