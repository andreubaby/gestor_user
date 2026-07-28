# =============================================================================
# Gestor de Usuarios Babyplant — Makefile
# =============================================================================
# Uso: make <target>
#
# Requisitos: Docker + Docker Compose (recomendado) o PHP 8.2+ / Node 22+ locales
# =============================================================================

.DEFAULT_GOAL := help
SHELL         := /bin/bash
DC            := docker compose
PHP_CONTAINER := php_gestor
QUEUE_CONTAINER := queue_gestor

# Colores
RESET  := \033[0m
BOLD   := \033[1m
GREEN  := \033[32m
YELLOW := \033[33m
CYAN   := \033[36m

# =============================================================================
# AYUDA
# =============================================================================

.PHONY: help
help: ## Muestra este mensaje de ayuda
	@printf "\n$(BOLD)$(CYAN)Gestor de Usuarios Babyplant$(RESET)\n\n"
	@printf "$(BOLD)Uso:$(RESET) make $(CYAN)<target>$(RESET)\n\n"
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ { printf "  $(CYAN)%-30s$(RESET) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@printf "\n"

# =============================================================================
# INSTALACIÓN INICIAL
# =============================================================================

.PHONY: install
install: ## Instalación completa (primera vez) con Docker
	@printf "\n$(BOLD)$(GREEN)▶ Instalación completa con Docker$(RESET)\n\n"
	@$(MAKE) env
	@$(MAKE) build
	@$(MAKE) up
	@printf "\n$(YELLOW)⏳ Esperando a que PHP esté listo...$(RESET)\n"
	@sleep 5
	@$(MAKE) key-generate
	@$(MAKE) migrate
	@printf "\n$(BOLD)$(GREEN)✔ Instalación completada$(RESET)\n"
	@printf "  → App: http://localhost:$${APP_PORT:-8077}\n\n"

.PHONY: install-local
install-local: ## Instalación sin Docker (PHP + Node locales)
	@printf "\n$(BOLD)$(GREEN)▶ Instalación local (sin Docker)$(RESET)\n\n"
	@$(MAKE) env
	composer install
	npm install
	cd "maria app" && npm install && cd ..
	php artisan key:generate
	php artisan migrate
	npm run build
	cd "maria app" && npm run build && cd ..
	@printf "\n$(BOLD)$(GREEN)✔ Instalación local completada$(RESET)\n"
	@printf "  → Ejecuta 'make dev' para arrancar el entorno de desarrollo\n\n"

.PHONY: env
env: ## Crea el archivo .env si no existe
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		printf "$(GREEN)✔ .env creado desde .env.example$(RESET)\n"; \
		printf "$(YELLOW)⚠ Edita .env con tus valores antes de continuar$(RESET)\n"; \
	else \
		printf "$(YELLOW)⚠ .env ya existe, no se sobreescribe$(RESET)\n"; \
	fi

# =============================================================================
# DOCKER
# =============================================================================

.PHONY: build
build: ## Construye las imágenes Docker
	$(DC) build php queue scheduler

.PHONY: up
up: ## Levanta todos los contenedores en segundo plano
	$(DC) up -d --build php nginx queue scheduler node db

.PHONY: down
down: ## Para y elimina los contenedores
	$(DC) down

.PHONY: stop
stop: ## Para los contenedores (sin eliminarlos)
	$(DC) stop

.PHONY: restart
restart: ## Reinicia todos los contenedores
	$(DC) restart

.PHONY: ps
ps: ## Estado de los contenedores
	$(DC) ps

.PHONY: up-openwa
up-openwa: ## Levanta el stack con OpenWA integrado (docker-compose.openwa.yml)
	docker compose -f docker-compose.openwa.yml up -d

.PHONY: down-openwa
down-openwa: ## Para el stack OpenWA
	docker compose -f docker-compose.openwa.yml down

# =============================================================================
# DESARROLLO LOCAL (sin Docker)
# =============================================================================

.PHONY: dev
dev: ## Arranca el entorno de desarrollo local (serve + queue + logs + vite)
	composer run dev

.PHONY: serve
serve: ## Arranca solo el servidor PHP
	php artisan serve

.PHONY: vite
vite: ## Arranca solo el servidor de Vite (frontend)
	npm run dev

# =============================================================================
# LARAVEL
# =============================================================================

.PHONY: key-generate
key-generate: ## Genera APP_KEY en .env
	docker exec $(PHP_CONTAINER) php artisan key:generate --force

.PHONY: migrate
migrate: ## Ejecuta las migraciones de base de datos
	docker exec $(PHP_CONTAINER) php artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## Recrea toda la base de datos (¡borra datos!)
	docker exec $(PHP_CONTAINER) php artisan migrate:fresh --force

.PHONY: migrate-rollback
migrate-rollback: ## Revierte la última migración
	docker exec $(PHP_CONTAINER) php artisan migrate:rollback

.PHONY: db-seed
db-seed: ## Ejecuta los seeders de la base de datos
	docker exec $(PHP_CONTAINER) php artisan db:seed

.PHONY: cache
cache: ## Genera los cachés de config, rutas y vistas (producción)
	docker exec $(PHP_CONTAINER) php artisan config:cache
	docker exec $(PHP_CONTAINER) php artisan route:cache
	docker exec $(PHP_CONTAINER) php artisan view:cache

.PHONY: cache-clear
cache-clear: ## Limpia todos los cachés
	docker exec $(PHP_CONTAINER) php artisan optimize:clear

.PHONY: tinker
tinker: ## Abre Laravel Tinker (REPL interactivo)
	docker exec -it $(PHP_CONTAINER) php artisan tinker

.PHONY: artisan
artisan: ## Ejecuta un comando artisan. Uso: make artisan CMD="queue:failed"
	docker exec $(PHP_CONTAINER) php artisan $(CMD)

# =============================================================================
# COLAS (QUEUE)
# =============================================================================

.PHONY: queue-failed
queue-failed: ## Lista los jobs fallidos
	docker exec $(PHP_CONTAINER) php artisan queue:failed

.PHONY: queue-retry
queue-retry: ## Reintenta todos los jobs fallidos
	docker exec $(PHP_CONTAINER) php artisan queue:retry all

.PHONY: queue-flush
queue-flush: ## Elimina todos los jobs fallidos
	docker exec $(PHP_CONTAINER) php artisan queue:flush

.PHONY: queue-heartbeat
queue-heartbeat: ## Verifica que el worker de colas está activo
	@docker exec $(QUEUE_CONTAINER) sh -c \
		"test -f /var/www/html/storage/framework/queue-worker-heartbeat \
		&& echo '✔ Worker activo' \
		|| echo '✗ Worker sin heartbeat (posible caída)'"

.PHONY: queue-work
queue-work: ## Arranca un worker de colas manualmente (local)
	php artisan queue:work database --sleep=1 --tries=3 --backoff=60

# =============================================================================
# OPENWA / WHATSAPP
# =============================================================================

.PHONY: openwa-validate
openwa-validate: ## Valida la configuración de OpenWA
	docker exec $(PHP_CONTAINER) php artisan openwa:validate

.PHONY: openwa-debug-phone
openwa-debug-phone: ## Depura resolución de teléfono para un usuario. Uso: make openwa-debug-phone USER_ID=123
	docker exec $(PHP_CONTAINER) php artisan openwa:debug-phone $(USER_ID)

# =============================================================================
# FICHAJES / SINCRONIZACIÓN
# =============================================================================

.PHONY: sync-users
sync-users: ## Sincroniza usuarios DB10 (trabajadores) → DB11 (fichajes)
	docker exec $(PHP_CONTAINER) php artisan fichajes:sync-users

.PHONY: sync-passwords
sync-passwords: ## Sincroniza contraseñas DB10 → DB11
	docker exec $(PHP_CONTAINER) php artisan fichajes:sync-passwords

# =============================================================================
# LOGS
# =============================================================================

.PHONY: logs
logs: ## Muestra logs de todos los contenedores en tiempo real
	$(DC) logs -f

.PHONY: logs-php
logs-php: ## Logs del contenedor PHP
	$(DC) logs -f php

.PHONY: logs-queue
logs-queue: ## Logs del contenedor de colas
	$(DC) logs -f queue

.PHONY: logs-app
logs-app: ## Últimas 50 líneas del log de Laravel
	docker exec $(PHP_CONTAINER) tail -n 50 storage/logs/laravel.log

.PHONY: logs-openwa
logs-openwa: ## Últimas 50 líneas del log de OpenWA
	docker exec $(PHP_CONTAINER) sh -c "tail -n 50 storage/logs/openwa-$$(date +%F).log 2>/dev/null || echo 'No hay log de OpenWA para hoy'"

# =============================================================================
# TESTS
# =============================================================================

.PHONY: test
test: ## Ejecuta todos los tests
	docker exec $(PHP_CONTAINER) php artisan test

.PHONY: test-openwa
test-openwa: ## Ejecuta solo los tests de OpenWA (zona crítica)
	docker exec $(PHP_CONTAINER) php artisan test --filter=OpenWA

.PHONY: test-webhook
test-webhook: ## Ejecuta solo los tests del webhook de OpenWA
	docker exec $(PHP_CONTAINER) php artisan test --filter=OpenWAWebhookControllerTest

.PHONY: test-local
test-local: ## Ejecuta los tests sin Docker
	php artisan test

# =============================================================================
# FRONTEND
# =============================================================================

.PHONY: npm-install
npm-install: ## Instala dependencias npm (principal + maria app)
	npm install
	cd "maria app" && npm install

.PHONY: npm-build
npm-build: ## Compila los assets de producción (principal + maria app)
	npm run build
	cd "maria app" && npm run build

# =============================================================================
# DESPLIEGUE
# =============================================================================

.PHONY: deploy
deploy: ## Despliega en producción (rebuild + migrate + cache)
	@printf "\n$(BOLD)$(GREEN)▶ Despliegue a producción$(RESET)\n\n"
	$(DC) up -d --build php queue scheduler node
	docker exec $(PHP_CONTAINER) php artisan migrate --force
	docker exec $(PHP_CONTAINER) php artisan optimize:clear
	docker exec $(PHP_CONTAINER) php artisan config:cache
	docker exec $(PHP_CONTAINER) php artisan route:cache
	docker exec $(PHP_CONTAINER) php artisan view:cache
	@printf "\n$(BOLD)$(GREEN)✔ Despliegue completado$(RESET)\n"
	@printf "  → Verifica: make smoke-test\n\n"

.PHONY: smoke-test
smoke-test: ## Comprobaciones rápidas post-despliegue
	@printf "\n$(BOLD)Smoke test post-deploy$(RESET)\n"
	@docker exec $(PHP_CONTAINER) php artisan openwa:validate && printf "$(GREEN)✔ OpenWA config OK$(RESET)\n" || printf "$(YELLOW)⚠ OpenWA config con advertencias$(RESET)\n"
	@$(MAKE) queue-heartbeat
	@docker exec $(PHP_CONTAINER) php artisan queue:failed | grep -q "No failed jobs" \
		&& printf "$(GREEN)✔ Sin jobs fallidos$(RESET)\n" \
		|| printf "$(YELLOW)⚠ Hay jobs fallidos — ejecuta: make queue-failed$(RESET)\n"

# =============================================================================
# LIMPIEZA
# =============================================================================

.PHONY: clean
clean: ## Para contenedores y elimina volúmenes (¡borra datos!)
	$(DC) down -v

.PHONY: clean-vendor
clean-vendor: ## Elimina vendor/ y node_modules/ para reinstalación limpia
	rm -rf vendor node_modules "maria app/node_modules"

.PHONY: reset
reset: ## Reseteo completo: down + clean + install (¡borra todo!)
	@printf "$(YELLOW)⚠ Esto eliminará contenedores, volúmenes y datos. ¿Continuar? [s/N] $(RESET)" \
		&& read CONFIRM && [ "$$CONFIRM" = "s" ] || { printf "Cancelado\n"; exit 1; }
	@$(MAKE) clean
	@$(MAKE) install
