.DEFAULT_GOAL := help

COMPOSE := docker compose
APP_EXEC := $(COMPOSE) exec -T app

.PHONY: help dev prod up down restart logs ps \
        migrate keygen build frontend-install \
        test test-fast test-feature test-clone lint shell \
        mcp-up mcp-down git-sync

help: ## Lista targets disponibles
	@grep -E '^[a-zA-Z0-9_.-]+:.*##' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

dev: ## Despliegue interactivo desarrollo (API key, repos, Docker, build)
	@bash scripts/deploy.sh development

prod: ## Despliegue interactivo producción (API key, repos, Docker, build)
	@bash scripts/deploy.sh production

up: ## Levantar stack Docker
	$(COMPOSE) up -d

down: ## Parar stack Docker
	$(COMPOSE) down

restart: ## Reiniciar contenedores
	$(COMPOSE) restart

logs: ## Seguir logs (app + nginx)
	$(COMPOSE) logs -f app nginx

ps: ## Estado de contenedores
	$(COMPOSE) ps

migrate: ## Migraciones pendientes (seguro, no borra datos)
	$(APP_EXEC) php artisan migrate --force

keygen: ## Generar APP_KEY
	$(APP_EXEC) php artisan key:generate --force

frontend-install: ## pnpm install
	pnpm install

build: ## Build frontend (vue-tsc + vite)
	pnpm run build

test: ## PHPUnit completo en Docker
	$(APP_EXEC) php artisan test

test-fast: ## PHPUnit rápido (Unit)
	$(APP_EXEC) php artisan test --testsuite=Unit

test-feature: ## PHPUnit suite Feature
	$(APP_EXEC) php artisan test --testsuite=Feature

test-clone: ## PHPUnit tests clone Bitbucket (US8)
	$(APP_EXEC) php artisan test --filter='BitbucketRepositoryUrlParserTest|CloneRepositoryServiceTest|CloneProjectTest'

mcp-up: ## Levantar MCP en Docker (profile mcp-docker)
	$(COMPOSE) --profile mcp-docker up -d mcp

mcp-down: ## Parar MCP en Docker
	$(COMPOSE) --profile mcp-docker stop mcp

git-sync: ## Sincronizar GIT_REPOS_URLS en bucle (profile git-sync)
	$(COMPOSE) --profile git-sync up -d repos-sync

lint: ## Pint (PHP) + ESLint (frontend)
	$(APP_EXEC) ./vendor/bin/pint --test
	pnpm lint

shell: ## Shell en contenedor app
	$(COMPOSE) exec app bash
