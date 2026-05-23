# 🎯 CHECKLIST DE INTEGRACIÓN OPENWA

Esta lista verifica que todos los componentes están correctamente implementados.

## ✅ Configuración

- [ ] `.env.example` actualizado con variables OPENWA
- [ ] `config/openwa.php` creado
- [ ] `config/logging.php` actualizado con canal `openwa`
- [ ] `bootstrap/providers.php` include `OpenWAServiceProvider`

## ✅ Servicios y Cliente

- [ ] `app/Services/OpenWA/OpenWAClient.php` - Cliente HTTP
  - [ ] Método `sendText()`
  - [ ] Método `sendTextToChatId()`
  - [ ] Método `getSession()`
  - [ ] Método `registerWebhook()`
  - [ ] Conversión teléfono → Chat ID
  - [ ] Logging sin exponer secretos

- [ ] `app/Services/WhatsApp/WhatsappNotificationService.php` - Servicio de dominio
  - [ ] Método `sendWelcomeMessage()`
  - [ ] Método `sendOtp()`
  - [ ] Método `sendOrderUpdate()`
  - [ ] Método `sendToUser()`
  - [ ] Método `sendToPhone()`
  - [ ] Método `sendToChatId()`

- [ ] `app/Providers/OpenWAServiceProvider.php` - Service Provider
  - [ ] Registra `OpenWAClient` como singleton
  - [ ] Registra `WhatsappNotificationService`
  - [ ] Registra comandos

## ✅ Excepciones

- [ ] `app/Exceptions/OpenWAException.php` - Excepción personalizada

## ✅ Persistencia

- [ ] `database/migrations/2026_05_21_000001_create_whatsapp_messages_table.php`
  - [ ] Tabla `whatsapp_messages`
  - [ ] Campos completos
  - [ ] Índices optimizados

- [ ] `database/migrations/2026_05_21_000002_add_phone_to_users_table.php`
  - [ ] Columna `phone` en `users`

- [ ] `app/Models/WhatsappMessage.php`
  - [ ] Relación con User
  - [ ] Scopes (inbound, outbound, forSession, etc)
  - [ ] Métodos (markAsSent, markAsDelivered, etc)

- [ ] `app/Models/User.php` actualizado
  - [ ] `phone` en fillable
  - [ ] Relación `whatsappMessages()`

## ✅ Jobs

- [ ] `app/Jobs/SendWhatsappMessageJob.php`
  - [ ] Envía desde Usuario en BD
  - [ ] Envía directo (chatId, text)
  - [ ] Reintentos configurados
  - [ ] Manejo de errores
  - [ ] Logging

## ✅ Webhooks

- [ ] `app/Http/Controllers/OpenWAWebhookController.php`
  - [ ] Ruta `POST /api/webhooks/openwa`
  - [ ] Validación HMAC
  - [ ] Idempotencia
  - [ ] Procesa `message.received`
  - [ ] Procesa `message.status`
  - [ ] Procesa `session.status`
  - [ ] Procesa `session.qr`
  - [ ] Procesa `session.disconnected`

- [ ] `routes/api.php` actualizado
  - [ ] Ruta del webhook registrada sin CSRF

## ✅ Comandos

- [ ] `app/Console/Commands/ValidateOpenwaConfig.php`
  - [ ] Comando `openwa:validate`
  - [ ] Valida env vars
  - [ ] Prueba conectividad
  - [ ] Verifica BD
  - [ ] Verifica queue
  - [ ] Verifica logs

## ✅ Tests

- [ ] `tests/Unit/Services/OpenWA/OpenWAClientTest.php`
  - [ ] Test sendText
  - [ ] Test sendTextToChatId
  - [ ] Test getSession
  - [ ] Test registerWebhook
  - [ ] Test excepciones
  - [ ] Test conversión teléfono

- [ ] `tests/Feature/Http/Controllers/OpenWAWebhookControllerTest.php`
  - [ ] Test recibir mensaje
  - [ ] Test ignorar propios
  - [ ] Test eventos sesión
  - [ ] Test validación HMAC
  - [ ] Test idempotencia
  - [ ] Test actualización estado

- [ ] `tests/Unit/Jobs/SendWhatsappMessageJobTest.php`
  - [ ] Test envío desde BD
  - [ ] Test envío directo
  - [ ] Test excepciones

- [ ] `tests/Feature/OpenWAIntegrationTest.php`
  - [ ] Test workflow completo
  - [ ] Test flujo webhook
  - [ ] Test prevención duplicados
  - [ ] Test actualización estados
  - [ ] Test relaciones User
  - [ ] Test queries
  - [ ] Test seguridad HMAC

## ✅ Documentación

- [ ] `OPENWA_README.md` - Documentación completa
  - [ ] Tabla de contenidos
  - [ ] Requisitos
  - [ ] Instalación
  - [ ] Variables de entorno
  - [ ] Uso del cliente
  - [ ] Uso del servicio
  - [ ] Uso de Jobs
  - [ ] Webhooks
  - [ ] Docker
  - [ ] API reference
  - [ ] Database schema
  - [ ] Tests
  - [ ] Troubleshooting

- [ ] `OPENWA_EXAMPLES.php` - Ejemplos prácticos
  - [ ] Envío desde controlador
  - [ ] OTP en registro
  - [ ] Envío en Mail
  - [ ] Uso de Job
  - [ ] Procesamiento de webhook
  - [ ] Listener personalizado
  - [ ] Mensajes dinámicos
  - [ ] Client directo
  - [ ] Comandos Artisan
  - [ ] Queries útiles

- [ ] `OPENWA_SETUP.md` - Resumen de setup
  - [ ] Lista de archivos generados
  - [ ] Estructura de carpetas
  - [ ] Características
  - [ ] Seguridad
  - [ ] Quick start

- [ ] `docker-compose.openwa.yml` - Configuración Docker
  - [ ] Servicio Laravel
  - [ ] Servicio OpenWA
  - [ ] DB MySQL
  - [ ] Redis
  - [ ] Network
  - [ ] Volúmenes
  - [ ] Health checks
  - [ ] Instrucciones

## ✅ Scripts

- [ ] `openwa-quickstart.sh` - Setup automático (Linux/Mac)
- [ ] `openwa-quickstart.ps1` - Setup automático (Windows)

## ✅ Validaciones de Seguridad

- [ ] API key nunca se loguea
- [ ] Webhook secret validado con HMAC
- [ ] Inyección de dependencias
- [ ] Validación tipada de inputs
- [ ] Exceptions claras
- [ ] Logs sin datos sensibles
- [ ] Idempotencia en webhooks
- [ ] Evita duplicados

## ✅ Validaciones Funcionales

Ejecutar en terminal:

```bash
# Verificar migraciones están aplicadas
php artisan migrate:status

# Ejecutar tests
php artisan test tests/Unit/Services/OpenWA/
php artisan test tests/Feature/Http/Controllers/
php artisan test tests/Unit/Jobs/
php artisan test tests/Feature/OpenWAIntegrationTest.php

# Validar configuración
php artisan openwa:validate

# Ver que ServiceProvider está registrado
php artisan tinker
>>> app(\App\Services\OpenWA\OpenWAClient::class)
>>> app(\App\Services\WhatsApp\WhatsappNotificationService::class)

# Probar envío de mensaje
>>> $user = \App\Models\User::first()
>>> app(\App\Services\WhatsApp\WhatsappNotificationService::class)->sendWelcomeMessage($user)

# Ver mensajes guardados
>>> \App\Models\WhatsappMessage::latest()->first()
```

## 📋 Verificación Previa a Producción

- [ ] Variables de entorno configuradas correctamente
- [ ] Base de datos migrada
- [ ] Queue worker funcionando
- [ ] OpenWA API accesible
- [ ] Webhook URL accesible desde OpenWA
- [ ] HMAC secret configurado
- [ ] Tests pasando
- [ ] Logs configurados
- [ ] Redis/Caché funcionando (si usas queue)
- [ ] Error handling probado
- [ ] Rate limiting en webhooks
- [ ] Monitoreo de logs activado
- [ ] Backups de BD configurados

## 🚀 Deployment Checklist

- [ ] `.env` tiene valores reales (no example)
- [ ] `APP_DEBUG=false` en producción
- [ ] `LOG_LEVEL=warning` en producción
- [ ] App key generado (`php artisan key:generate`)
- [ ] Storage permissions correctos (`chmod 775 storage`)
- [ ] Migraciones ejecutadas en prod
- [ ] Cache limpio (`php artisan config:cache`)
- [ ] Routes cacheadas (`php artisan route:cache`)
- [ ] Queue worker supervisado (upstart, systemd, etc)
- [ ] Logs rotados y monitoreados
- [ ] Backups automáticos de DB
- [ ] SSL/HTTPS en producción
- [ ] CORS configurado si es necesario
- [ ] Rate limiting en webhooks

---

## 📞 Support

Si encuentras problemas:

1. Ejecuta `php artisan openwa:validate` para diagnóstico
2. Revisa `storage/logs/openwa.log` para errores
3. Mira la sección de Troubleshooting en `OPENWA_README.md`
4. Consulta `OPENWA_EXAMPLES.php` para ejemplos de uso

---

**Última validación:** Mayo 21, 2026

