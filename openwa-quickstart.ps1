# ═══════════════════════════════════════════════════════════════════════════════
# QUICK START - INTEGRACIÓN OPENWA EN LARAVEL (Windows PowerShell)
# ═══════════════════════════════════════════════════════════════════════════════
#
# Uso:
#   .\openwa-quickstart.ps1
# ═══════════════════════════════════════════════════════════════════════════════

$ErrorActionPreference = "Stop"

Write-Host "╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  OpenWA Quick Start para Laravel                             ║" -ForegroundColor Cyan
Write-Host "║  Integración de WhatsApp Gateway                             ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ─────────────────────────────────────────────────────────────────────────────
# 1. Verificar requisitos
# ─────────────────────────────────────────────────────────────────────────────

Write-Host "📋 Verificando requisitos..." -ForegroundColor Blue
Write-Host ""

# Verificar que estamos en el directorio correcto
if (!(Test-Path "artisan")) {
    Write-Host "❌ No se encontró artisan. ¿Estás en el directorio de Laravel?" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Laravel project detectado" -ForegroundColor Green

# ─────────────────────────────────────────────────────────────────────────────
# 2. Configurar variables de entorno
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "⚙️  Configurando variables de entorno..." -ForegroundColor Blue
Write-Host ""

# Verificar si .env existe
if (!(Test-Path ".env")) {
    Write-Host "Copiando .env.example a .env..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    Write-Host "✅ .env creado" -ForegroundColor Green
}

# Pedir valores al usuario
Write-Host ""
Write-Host "Necesito algunos datos para configurar OpenWA:" -ForegroundColor Cyan
Write-Host ""

$OPENWA_BASE_URL = Read-Host "🔗 URL base de OpenWA (ej: http://openwa:3000)"
$OPENWA_API_KEY = Read-Host "🔑 API Key de OpenWA"
$OPENWA_SESSION_ID = Read-Host "🔐 Session ID (Enter para 'default')"
if ([string]::IsNullOrWhiteSpace($OPENWA_SESSION_ID)) { $OPENWA_SESSION_ID = "default" }

$OPENWA_WEBHOOK_SECRET = Read-Host "🔓 Webhook Secret (opcional, Enter para saltear)"
$COUNTRY_CODE_INPUT = Read-Host "🌍 Código de país (Enter para '34')"
if ([string]::IsNullOrWhiteSpace($COUNTRY_CODE_INPUT)) { $COUNTRY_CODE_INPUT = "34" }
$OPENWA_DEFAULT_COUNTRY_CODE = $COUNTRY_CODE_INPUT

Write-Host ""
Write-Host "Actualizando .env..." -ForegroundColor Yellow

# Leer .env
$envContent = Get-Content ".env" -Raw

# Actualizar valores
$envContent = $envContent -replace 'OPENWA_BASE_URL=.*', "OPENWA_BASE_URL=$OPENWA_BASE_URL"
$envContent = $envContent -replace 'OPENWA_API_KEY=.*', "OPENWA_API_KEY=$OPENWA_API_KEY"
$envContent = $envContent -replace 'OPENWA_SESSION_ID=.*', "OPENWA_SESSION_ID=$OPENWA_SESSION_ID"
if ($OPENWA_WEBHOOK_SECRET) {
    $envContent = $envContent -replace 'OPENWA_WEBHOOK_SECRET=.*', "OPENWA_WEBHOOK_SECRET=$OPENWA_WEBHOOK_SECRET"
}
$envContent = $envContent -replace 'OPENWA_DEFAULT_COUNTRY_CODE=.*', "OPENWA_DEFAULT_COUNTRY_CODE=$OPENWA_DEFAULT_COUNTRY_CODE"

# Escribir .env
Set-Content ".env" $envContent -Encoding UTF8

Write-Host "✅ Variables de entorno actualizadas" -ForegroundColor Green

# ─────────────────────────────────────────────────────────────────────────────
# 3. Ejecutar migraciones
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "💾 Ejecutando migraciones..." -ForegroundColor Blue

$result = & php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Base de datos actualizada" -ForegroundColor Green
} else {
    Write-Host "⚠️  Hubo un problema al ejecutar migraciones" -ForegroundColor Yellow
}

# ─────────────────────────────────────────────────────────────────────────────
# 4. Validar configuración
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "📝 Validando configuración..." -ForegroundColor Blue

php artisan openwa:validate

# ─────────────────────────────────────────────────────────────────────────────
# 5. Información final
# ─────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║  ✅ OpenWA está configurado                                  ║" -ForegroundColor Green
Write-Host "╚═══════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📚 Próximos pasos:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Revisar la documentación:" -ForegroundColor Blue
Write-Host "   type OPENWA_README.md"
Write-Host ""
Write-Host "2. Ver ejemplos de uso:" -ForegroundColor Blue
Write-Host "   type OPENWA_EXAMPLES.php"
Write-Host ""
Write-Host "3. Procesar jobs en background:" -ForegroundColor Blue
Write-Host "   php artisan queue:work"
Write-Host ""
Write-Host "4. Registrar webhook en OpenWA:" -ForegroundColor Blue
Write-Host "   php artisan openwa:register-webhook"
Write-Host ""
Write-Host "5. Monitorear logs:" -ForegroundColor Blue
Write-Host "   Get-Content storage/logs/openwa.log -Wait"
Write-Host ""

Write-Host "💡 Ejemplo de uso en código:" -ForegroundColor Cyan
Write-Host ""
Write-Host "  app(\App\Services\WhatsApp\WhatsappNotificationService::class)" -ForegroundColor Yellow
Write-Host "    ->sendWelcomeMessage(`$user);" -ForegroundColor Yellow
Write-Host ""

Write-Host "✨ ¡Listo para usar!" -ForegroundColor Green

