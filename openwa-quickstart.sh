#!/bin/bash

# ═══════════════════════════════════════════════════════════════════════════════
# QUICK START - INTEGRACIÓN OPENWA EN LARAVEL
# ═══════════════════════════════════════════════════════════════════════════════
#
# Este script te guía por los pasos para activar OpenWA en tu proyecto
#
# Uso:
#   chmod +x ./openwa-quickstart.sh
#   ./openwa-quickstart.sh
# ═══════════════════════════════════════════════════════════════════════════════

set -e

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  OpenWA Quick Start para Laravel                             ║"
echo "║  Integración de WhatsApp Gateway                             ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# ─────────────────────────────────────────────────────────────────────────────
# 1. Verificar requisitos
# ─────────────────────────────────────────────────────────────────────────────

echo "📋 Verificando requisitos..."
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ No se encontró artisan. ¿Estás en el directorio de Laravel?"
    exit 1
fi

echo "✅ Laravel project detectado"

# ─────────────────────────────────────────────────────────────────────────────
# 2. Configurar variables de entorno
# ─────────────────────────────────────────────────────────────────────────────

echo ""
echo "⚙️  Configurando variables de entorno..."
echo ""

# Verificar si .env existe
if [ ! -f ".env" ]; then
    echo "Copiando .env.example a .env..."
    cp .env.example .env
    echo "✅ .env creado"
fi

# Pedir valores al usuario
echo ""
echo "Necesito algunos datos para configurar OpenWA:"
echo ""

read -p "🔗 URL base de OpenWA (ej: http://openwa:3000): " OPENWA_BASE_URL
read -p "🔑 API Key de OpenWA: " OPENWA_API_KEY
read -p "🔐 Session ID (Enter para 'default'): " OPENWA_SESSION_ID
OPENWA_SESSION_ID=${OPENWA_SESSION_ID:-default}

read -p "🔓 Webhook Secret (opcional, Enter para saltear): " OPENWA_WEBHOOK_SECRET
COUNTRY_CODE_INPUT=""
read -p "🌍 Código de país (Enter para '34'): " COUNTRY_CODE_INPUT
OPENWA_DEFAULT_COUNTRY_CODE=${COUNTRY_CODE_INPUT:-34}

echo ""
echo "Actualizando .env..."

# Actualizar .env (funciona en Linux/Mac) - para Windows modificar según sea necesario
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s|OPENWA_BASE_URL=.*|OPENWA_BASE_URL=$OPENWA_BASE_URL|" .env
    sed -i '' "s|OPENWA_API_KEY=.*|OPENWA_API_KEY=$OPENWA_API_KEY|" .env
    sed -i '' "s|OPENWA_SESSION_ID=.*|OPENWA_SESSION_ID=$OPENWA_SESSION_ID|" .env
    sed -i '' "s|OPENWA_WEBHOOK_SECRET=.*|OPENWA_WEBHOOK_SECRET=$OPENWA_WEBHOOK_SECRET|" .env
    sed -i '' "s|OPENWA_DEFAULT_COUNTRY_CODE=.*|OPENWA_DEFAULT_COUNTRY_CODE=$OPENWA_DEFAULT_COUNTRY_CODE|" .env
else
    # Linux
    sed -i "s|OPENWA_BASE_URL=.*|OPENWA_BASE_URL=$OPENWA_BASE_URL|" .env
    sed -i "s|OPENWA_API_KEY=.*|OPENWA_API_KEY=$OPENWA_API_KEY|" .env
    sed -i "s|OPENWA_SESSION_ID=.*|OPENWA_SESSION_ID=$OPENWA_SESSION_ID|" .env
    sed -i "s|OPENWA_WEBHOOK_SECRET=.*|OPENWA_WEBHOOK_SECRET=$OPENWA_WEBHOOK_SECRET|" .env
    sed -i "s|OPENWA_DEFAULT_COUNTRY_CODE=.*|OPENWA_DEFAULT_COUNTRY_CODE=$OPENWA_DEFAULT_COUNTRY_CODE|" .env
fi

echo "✅ Variables de entorno actualizadas"

# ─────────────────────────────────────────────────────────────────────────────
# 3. Ejecutar migraciones
# ─────────────────────────────────────────────────────────────────────────────

echo ""
echo "💾 Ejecutando migraciones..."
php artisan migrate --force
echo "✅ Base de datos actualizada"

# ─────────────────────────────────────────────────────────────────────────────
# 4. Validar configuración
# ─────────────────────────────────────────────────────────────────────────────

echo ""
echo "📝 Validando configuración..."
php artisan openwa:validate

# ─────────────────────────────────────────────────────────────────────────────
# 5. Información final
# ─────────────────────────────────────────────────────────────────────────────

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  ✅ OpenWA está configurado                                  ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

echo "📚 Próximos pasos:"
echo ""
echo "1. Revisar la documentación:"
echo "   cat OPENWA_README.md"
echo ""
echo "2. Ver ejemplos de uso:"
echo "   cat OPENWA_EXAMPLES.php"
echo ""
echo "3. Procesar jobs en background:"
echo "   php artisan queue:work"
echo ""
echo "4. Registrar webhook en OpenWA:"
echo "   php artisan openwa:register-webhook"
echo ""
echo "5. Monitorear logs:"
echo "   tail -f storage/logs/openwa.log"
echo ""

echo "💡 Ejemplo de uso en código:"
echo ""
echo "  app(\App\Services\WhatsApp\WhatsappNotificationService::class)"
echo "    ->sendWelcomeMessage(\$user);"
echo ""

echo "✨ ¡Listo para usar!"

