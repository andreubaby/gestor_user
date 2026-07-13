# GOTCHAS — lo que no es obvio leyendo por encima

Esto complementa a `RUNBOOK.md` (cómo operar) y a `docs/PROJECT_DOCUMENTATION.md` (arquitectura). Aquí va lo otro: comportamientos raros, decisiones implícitas, hardcodeos y bombas de relojería encontradas leyendo el código real. Si vas a tocar algo de esta lista, léela primero — te ahorra un bug en producción.

## 1. El cache de catálogos NUNCA se invalida solo

`CatalogosService::getCatalogos()` cachea usuarios/trabajadores de los 9 sistemas durante **1 hora** (`Cache::remember`, TTL fijo en el código). Existe `CatalogosService::invalidate()` y su docblock dice *"llama a este método en los Observers / después de crear/editar/borrar usuarios"* — **pero no hay ningún Observer registrado en el proyecto** (`grep observe(` no devuelve nada) y nada llama a `invalidate()` salvo un comando manual documentado en `RUNBOOK.md` (§ despliegue).

Consecuencia real: si das de alta un usuario nuevo en cualquiera de los 9 sistemas, **puede tardar hasta 1 hora en aparecer** en los desplegables de vinculación/autolink, o hasta que alguien ejecute:
```bash
php artisan tinker --execute="App\Services\CatalogosService::invalidate();"
```
Si vas a construir sincronización automática entre sistemas, este es el primer sitio a arreglar (Observer real o invalidación tras cada `store`/`update`).

## 2. `VinculacionService::save()` puede borrar vínculos si no pasas todos los IDs

```php
UsuarioVinculado::updateOrCreate(['uuid' => $data['uuid']], [
    'usuario_id' => $data['usuario_id'] ?? null,
    ... // 9 campos más, todos con ?? null
]);
```
Es un `updateOrCreate` con **reemplazo completo**: cualquier campo que no venga en `$data` se graba como `null`, no se preserva el valor existente. Si un formulario o script solo manda 3 de los 9 IDs para actualizar un vínculo existente, **los otros 6 quedan huérfanos**. `BulkUsuarioActionService::autoLinkByEmail()` sí construye el payload completo antes de llamar a `save()`, pero cualquier código nuevo que llame a este servicio tiene que hacer lo mismo o replicará el bug.

Relacionado: la búsqueda de "¿ya existe un vínculo para esta persona?" en `autoLinkByEmail()` solo comprueba `trabajador_id`, `usuario_id` y `pluton_id` (3 de 9 posibles). Si alguien ya está vinculado únicamente por, por ejemplo, `user_cronos_id`, el auto-link puede crear un **segundo `UsuarioVinculado` duplicado** con un `uuid` distinto para la misma persona.

## 3. Vacaciones: `vacation_year` (año de devengo) ≠ año calendario

`AusenciasService` distingue entre el año en que cae la fecha (`calendar_year`) y el "bucket" al que se imputa (`vacation_year`) — el mismo día puede estar guardado bajo un año de devengo distinto al año natural. Al borrar días:

- Para tipo **V** (vacaciones): se filtra también por `vacation_year`.
- Para **P/B/L** (permisos/bajas/libres): **no** se filtra por `vacation_year` a propósito, porque el día es único independientemente del año de devengo.

Si tocas `storeDays()`, respeta esa asimetría — no es un descuido, es intencional, pero si no lo sabes parece un bug.

### Arrastre automático en el PDF de vacaciones

`extendConsecutivosPosteriores()` genera el PDF de un rango de días, pero **antes de renderizar añade automáticamente hasta 31 días más** de después del último día seleccionado si son consecutivos y del mismo tipo — aunque no estuvieran en la query original. Es el "arrastre de días consecutivos" que menciona el README, pero si generas un PDF esperando exactamente los días que pediste, puede salir un rango más largo.

También hay logging de diagnóstico muy verboso (`Log::info` en casi cada paso) dejado a propósito en `streamPdfVacaciones()` — parece que hubo un bug complicado de rastrear ahí. Si vas a limpiar logs "ruidosos", revisa antes si ese endpoint sigue dando problemas.

## 4. Bienestar: dos fuentes de datos con prioridad, no una sola

`BienestarService::moodPorWorkerId()` mezcla dos sistemas por semana:
1. **Punch.mood** (sistema nuevo, `mysql_fichajes`) — se usa si el trabajador tiene `user_fichaje_id` vinculado y hay dato esa semana.
2. **Fichar.bienestar** (sistema legacy, `mysql_trabajadores`) — fallback si no hay dato en Punch para esa semana concreta.

Es una prioridad **por semana individual**, no por trabajador completo: un mismo trabajador puede mostrar 2 semanas del sistema nuevo y 2 del legacy en el mismo carrusel de 4 semanas, dependiendo de qué sistema tenga datos cada semana. Los valores siempre se recortan a 1–4 (`max(1, min(4, ...))`).

## 5. Cálculo de horas trabajadas: pausas hardcodeadas por turno

`FichajesDiariosService::getBreakWindowsForShift()` resta tiempo de pausa **con horarios fijos en el código**, no configurables:

| Turno (`$t->turno`, default `'office'`) | Pausas descontadas |
|---|---|
| `office` | 10:00–10:30 siempre; +14:00–16:00 si la última salida es después de las 16:00 |
| `campaign` | 10:00–10:30 siempre; +14:00–15:00 si la última salida es después de las 16:00 |
| `intensive` | Solo **una** pausa: la de mañana (10:00–10:30) o la de tarde (19:00–19:30), la que tenga más solape — nunca las dos |
| Cualquier otro valor de `turno` | Ninguna pausa descontada (0 minutos) |

Si en algún momento cambian los horarios oficiales de descanso, hay que tocar este método — no hay configuración externa. Y si a un trabajador le asignan un `turno` con un valor que no sea exactamente `office`/`campaign`/`intensive`, las horas se calculan **sin descontar ninguna pausa**, sin avisar.

También: la columna FK de la tabla legacy `mysql_trabajadores.fichar` **se detecta dinámicamente** (`detectFicharTrabajadorFkColumn()`) probando una lista de nombres candidatos y, si nada coincide, buscando cualquier columna que contenga "trabaj". Si esa tabla remota cambia de esquema, esto puede romperse con un `RuntimeException` en tiempo de ejecución, o peor, detectar la columna equivocada silenciosamente.

## 6. Aviso de "no fichaste": exclusiones que hay que conocer

`MissingPunchReminderService` no avisa a todo el que falte, excluye por tres vías distintas:
- **`config('fichajes.missing_punch.omit_emails')`**: lista fija de ~10 emails (cuentas de gerencia/técnico/admin) que nunca reciben el aviso automático.
- **`work_mode = 'campaign'`** en `mysql_fichajes.users`: a estos se les considera fuera de horario fijo, se omiten.
- **Días laborables por email** (`config('fichajes.missing_punch.workdays_by_email')`): hay **un empleado concreto hardcodeado** (`diegojimenez291995@gmail.com`) con días laborables distintos al default (`[7,1,2,3]` en vez de lunes–viernes). Si esa persona cambia de horario otra vez, o se va, hay que tocar el `.php` de config y redesplegar — no hay UI para esto.

Deduplicación de envío: usa `Cache::add` con clave `date+trabajador_id` y TTL de 2 días para no reenviar si el comando se ejecuta dos veces el mismo día. Depende del store de cache configurado (ver punto 9).

Detección de festivos: primero mira fechas fijas en `MISSING_PUNCH_HOLIDAYS` (env, CSV), si no, busca dinámicamente una tabla de festivos en `mysql_polifonia` probando nombres de tabla/columna candidatos — si esa tabla se renombra, deja de detectar festivos **sin error visible**, simplemente actúa como si no hubiera festivo.

## 7. WhatsApp / OpenWA: cosas que no cuadran con lo que dice `AGENTS.md`

`AGENTS.md` dice *"para normalizar números, reutiliza `OpenWAClient::phoneToChatId()`"*. En la práctica **hay al menos dos implementaciones independientes** del mismo algoritmo:
- `OpenWAClient::phoneToChatId()` (protected, solo se usa dentro de la propia clase)
- `WhatsappNotificationService::phoneToChatId()` (su propia copia, casi idéntica)

Si el formato de número cambia (otro país, otro prefijo), hay que actualizar **ambas** o divergen silenciosamente.

### Bug potencial: los reintentos de un job de automatización pueden no reenviar nada

`SendAutomaticMessageStepJob` adquiere un lock de deduplicación (`Cache::add`, TTL 1 día) **al principio** de `handle()`, antes de intentar el envío. Si el envío falla a mitad (excepción) y Laravel reintenta el mismo job (tiene `tries = 3`), el segundo intento **vuelve a pedir el mismo lock, que ya existe**, así que el job registra `duplicate_blocked` y **retorna sin enviar nada** — dando la impresión de que "reintentó" cuando en realidad no volvió a intentar el envío real. El lock no se libera en caso de fallo.

### Resolución de teléfono silenciosa

`WhatsappNotificationService::resolvePhone()` para un `User` requiere que exista un `UsuarioVinculado` con `trabajador_id`, y de ahí saca el `tfno` (primero `mysql_trabajadores`, si no `mysql_polifonia`). Si el usuario no está vinculado, `sendWelcomeMessage()` y `sendOtp()` **no lanzan error, solo loguean un warning y no hacen nada** — un OTP o bienvenida puede "perderse" en silencio si falta el vínculo.

### `sendFileToChatId` prueba 3 endpoints por ensayo y error

Para enviar un archivo, `OpenWAClient` intenta `/messages/send-file`, luego `/messages/send-media`, luego `/messages/send-document`, quedándose con el primero que no lance excepción. Es tolerancia a distintas versiones del gateway OpenWA, pero significa que **no hay un único contrato conocido** de qué endpoint responde realmente en este entorno.

### Auto-recuperación de sesión OpenWA

Si una petición devuelve 404 o "session not active", `OpenWAClient` consulta `/api/sessions`, busca una sesión conectada y **reintenta con ese `session_id`, sustituyendo silenciosamente el configurado en `OPENWA_SESSION_ID`** para el resto de la vida de esa instancia del cliente. Útil para resiliencia, pero significa que "qué sesión de WhatsApp se está usando realmente" puede no coincidir con la variable de entorno si esa sesión cayó en algún momento.

### El constructor de `OpenWAClient` explota si falta configuración

Si `OPENWA_BASE_URL` u `OPENWA_API_KEY` no están seteadas, **el constructor lanza `OpenWAException` inmediatamente**, no al intentar enviar un mensaje. Como `WhatsappNotificationService` inyecta `OpenWAClient` por constructor, cualquier controlador que dependa de ese servicio fallará al instanciarse en un entorno sin OpenWA configurado, aunque la petición no tenga nada que ver con WhatsApp.

## 8. Modelos duplicados y uno roto

- **`Trabajador` y `TrabajadorPolifonia`** apuntan exactamente a la misma tabla (`mysql_polifonia.trabajadores`). `TrabajadorPolifonia` es la versión "completa" (fillable, timestamps desactivados) y es la que se usa en casi todos los Services; `Trabajador` es un esqueleto que solo usan `SyncFichajesUsers` y `GroupAssignmentController`. Fácil de confundir — antes de crear un modelo nuevo para Polifonía, revisa si ya existe uno de los dos.
- **`UsuarioPolifonia`** no se usa en ningún sitio del código (`grep` no encuentra referencias fuera del propio archivo) y además está **roto**: su accessor `getPasswordAttribute()` llama a `Crypt::decryptString()` sin importar el facade `Crypt` — si alguna vez se instancia y se lee `->password`, lanza un error fatal de clase no encontrada. Es código muerto que probablemente se debería borrar, no reutilizar.

## 9. Todo lo que depende del cache store compartido

Al menos tres mecanismos de idempotencia/dedup dependen de que el store de `CACHE_STORE` sea el mismo entre todas las instancias/workers:
1. Idempotencia del webhook OpenWA (`OPENWA_WEBHOOK_IDEMPOTENCY_TTL_SECONDS`).
2. Dedup de avisos de "no fichaste" (`missing-punch-reminder:{fecha}:{trabajador_id}`).
3. Dedup de pasos de automatizaciones WhatsApp (`automation-step-executed:{md5}`).

En local con `CACHE_STORE=database` (valor de `.env.example`) esto funciona mientras haya una sola instancia de la app. En cuanto se despliegue en más de un nodo/contenedor con cachés no compartidos, los tres mecanismos dejan de proteger contra duplicados — ver también la nota de `docs/PROJECT_DOCUMENTATION.md` sobre usar Redis en multi-instancia.

## 10. Contraseñas: política inconsistente entre los 9 sistemas

Este gestor es el que **crea/edita contraseñas** para todos los sistemas vinculados, pero la validación no es uniforme:

| Dónde | Regla |
|---|---|
| Registro en el gestor principal (`AuthController`) | Fuerte + comprobación contra HaveIBeenPwned (`Password::defaults()->uncompromised()`) |
| Alta de trabajador en Fichajes (`FichajeController`) | mínimo 12 |
| Edición de usuario en Fichajes (`UserFichajeController`) | mínimo 8, con confirmación |
| Usuarios/Buscador/Cronos/Plutón/Semillas/Store/Zeus/Worker Buscador | **mínimo 4 caracteres**, sin más requisitos |

Si vas a endurecer seguridad, este es el punto más flojo: la mayoría de sistemas externos aceptan contraseñas de 4 caracteres puestas desde aquí.

## 11. Exportaciones Excel que nunca se limpian

`GenerateFichajesExcelJob` guarda los `.xlsx` generados en `storage/app/exports/` de forma persistente (a diferencia de las descargas síncronas, que se borran tras enviarse con `deleteFileAfterSend(true)`). **No hay ningún comando o job que limpie esa carpeta** — crece indefinidamente con cada exportación en background. Si el disco empieza a llenarse, mirar ahí primero.

## 12. Configuración con contenido "de negocio", no de infraestructura

Dos archivos de config no son configuración técnica sino contenido operativo que alguien de RRHH podría necesitar cambiar, y que un desarrollador nuevo no esperaría encontrar en `config/`:

- **`config/onboarding.php`**: enlaces de Google Drive hardcodeados (documentos de prevención, planes, cursos) para el mensaje de bienvenida/onboarding. Si esos documentos se mueven o se revocan los enlaces de Drive, el onboarding envía links rotos y nadie lo sabrá hasta que un trabajador se queje.
- **`config/rrhh_docs.php`**: cada plantilla PDF referencia un archivo físico en `storage/app/rrhh_templates/` **por nombre exacto, incluyendo espacios** (ej. `'rrhh_templates/1 Entrega EPIS Fumigador.pdf'`). Si alguien renombra o reemplaza esos PDFs sin actualizar este archivo, la generación de documentos falla en silencio o genera el PDF equivocado.

## 13. Grupo "mock" de WhatsApp en desarrollo

`AutomationSequenceController::getMockOpenwaGroups()` devuelve 3 grupos de ejemplo (incluido uno llamado "👥 Todos" con un `chat_id` de aspecto real) **cuando la API de OpenWA no responde o falla**. Es solo un fallback de desarrollo, pero si alguna vez se usa en un entorno donde OpenWA está caído, un usuario podría intentar enviar un mensaje real a ese `chat_id` de mentira sin darse cuenta de que es un mock.

## 14. Checklist rápido antes de tocar zonas sensibles

- ¿Vas a cambiar vinculaciones (`UsuarioVinculado`)? → revisa el punto 2 (payload completo) antes de llamar a `save()`.
- ¿Vas a tocar cálculo de horas/pausas? → revisa el punto 5, los horarios están hardcodeados por `turno`.
- ¿Vas a cambiar el formato de teléfono/chat ID? → búscalo en los dos sitios del punto 7, no en uno.
- ¿Vas a añadir un nuevo Service que dependa de catálogos cacheados? → ten en cuenta el punto 1, los datos pueden tener hasta 1h de retraso.
- ¿Vas a desplegar en más de una instancia? → lee el punto 9 antes de asumir que la deduplicación funciona igual.
