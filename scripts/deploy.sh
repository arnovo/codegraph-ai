#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck source=scripts/lib/env.sh
source "${ROOT_DIR}/scripts/lib/env.sh"

MODE="${1:-}"

usage() {
  echo "Uso: $0 development|production" >&2
  exit 1
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Error: falta comando '$1' en PATH." >&2
    exit 1
  fi
}

prompt_default() {
  local label="$1"
  local default="$2"
  local answer

  read -r -p "${label} [${default}]: " answer
  if [[ -z "$answer" ]]; then
    echo "$default"
  else
    echo "$answer"
  fi
}

prompt_secret() {
  local label="$1"
  local answer

  read -r -s -p "${label}: " answer
  echo "" >&2
  echo "$answer"
}

is_yes() {
  case "$(printf '%s' "$1" | tr '[:upper:]' '[:lower:]')" in
    s|si|y|yes) return 0 ;;
    *) return 1 ;;
  esac
}

choose_agent_engine() {
  upsert_env "AGENT_ENGINE" "internal"
  configure_llm_api_key
}

configure_llm_api_key() {
  upsert_env "LLM_DRIVER" "$(prompt_default "LLM_DRIVER" "openai")"
  upsert_env "LLM_BASE_URL" "$(prompt_default "LLM_BASE_URL" "https://api.openai.com/v1")"
  upsert_env "LLM_MODEL" "$(prompt_default "LLM_MODEL" "gpt-4o-mini")"

  if env_is_empty "LLM_API_KEY"; then
    local api_key
    api_key="$(prompt_secret "LLM_API_KEY")"
    upsert_env "LLM_API_KEY" "\"${api_key}\""
  else
    echo "LLM_API_KEY ya definida en .env — se conserva."
  fi
}

configure_repos() {
  local repos_path
  repos_path="$(prompt_default "REPOS_HOST_PATH (carpeta con clones Git)" "${REPOS_DEFAULT}")"

  if [[ ! -d "$repos_path" ]]; then
    read -r -p "La ruta no existe. ¿Crearla? [s/N]: " create_repos
    if is_yes "$create_repos"; then
      mkdir -p "$repos_path"
    fi
  fi

  upsert_env "REPOS_HOST_PATH" "$repos_path"
  upsert_env "REPOS_CONTAINER_PATH" "$repos_path"
}

configure_git_sync() {
  echo ""
  echo "Git — sync automático planificado (repos-sync en Docker)."
  echo "Para clonar desde la UI ya disponible: Proyectos → Nuevo proyecto → Bitbucket."
  echo ""

  upsert_env "GIT_ALLOWED_HOSTS" "$(prompt_default "GIT_ALLOWED_HOSTS" "bitbucket.org")"
  upsert_env "GIT_CLONE_TIMEOUT_SECONDS" "$(prompt_default "GIT_CLONE_TIMEOUT_SECONDS" "600")"
  upsert_env "GIT_USERNAME" "$(prompt_default "GIT_USERNAME" "")"

  if env_is_empty "GIT_TOKEN"; then
    local git_token
    git_token="$(prompt_secret "GIT_TOKEN (app password / PAT)")"
    upsert_env "GIT_TOKEN" "\"${git_token}\""
  fi

  local repos_urls
  repos_urls="$(prompt_default "GIT_REPOS_URLS (URLs separadas por espacio)" "")"
  upsert_env "GIT_REPOS_URLS" "\"${repos_urls}\""

  read -r -p "¿Activar GIT_SYNC_ON_START? [s/N]: " sync_start
  if is_yes "$sync_start"; then
    upsert_env "GIT_SYNC_ON_START" "true"
    upsert_env "GIT_SYNC_INTERVAL_MINUTES" "$(prompt_default "GIT_SYNC_INTERVAL_MINUTES" "60")"
  else
    upsert_env "GIT_SYNC_ON_START" "false"
  fi

  upsert_env "GIT_USER_NAME" "$(prompt_default "GIT_USER_NAME (identidad git)" "Codebase Assistant")"
  upsert_env "GIT_USER_EMAIL" "$(prompt_default "GIT_USER_EMAIL" "codebase-assistant@example.com")"
}

prompt_optional_git_sync() {
  read -r -p "¿Configurar Git (hosts clone UI + credenciales sync planificado)? [s/N]: " git_sync
  if is_yes "$git_sync"; then
    configure_git_sync
  else
    upsert_env "GIT_ALLOWED_HOSTS" "bitbucket.org"
    upsert_env "GIT_CLONE_TIMEOUT_SECONDS" "600"
  fi
}

setup_development() {
  REPOS_DEFAULT="${HOME}/APPs"

  echo "=== Despliegue desarrollo ==="

  if [[ ! -f "${ROOT_DIR}/.env" ]]; then
    cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
    echo "Creado .env desde .env.example"
  fi

  cd "${ROOT_DIR}"

  upsert_env "APP_ENV" "local"
  upsert_env "APP_DEBUG" "true"
  upsert_env "APP_URL" "$(prompt_default "APP_URL" "http://localhost:8080")"
  upsert_env "APP_PORT" "$(prompt_default "APP_PORT" "8080")"
  upsert_env "LOG_LEVEL" "debug"
  upsert_env "LLM_LOG_TRAFFIC" "true"

  upsert_env "MCP_ON_HOST" "true"
  upsert_env "MCP_RPC_URL" "http://host.docker.internal:9749/rpc"
  upsert_env "MCP_UI_URL" "$(prompt_default "MCP_UI_URL" "http://localhost:9749")"

  configure_repos
  choose_agent_engine
  prompt_optional_git_sync

  echo ""
  echo "Levantando Docker..."
  docker compose up -d --build

  if env_is_empty "APP_KEY"; then
    docker compose exec -T app php artisan key:generate --force
  fi

  docker compose exec -T app php artisan migrate --force

  echo ""
  echo "Instalando dependencias frontend..."
  require_cmd pnpm
  pnpm install
  pnpm run build

  local app_url
  app_url="$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"')"

  echo ""
  echo "=== Desarrollo listo ==="
  echo "App:     ${app_url}"
  echo "Spec:    ${app_url}/internal/spec-status"
  echo ""
  echo "Siguiente paso (obligatorio para tools de código):"
  echo "  codebase-memory-mcp --ui=true --port=9749"
  echo ""
  echo "Luego: indexar repo en panel Proyectos → seleccionar activo → chatear."
}

setup_production() {
  REPOS_DEFAULT="/opt/repos"

  echo "=== Despliegue producción ==="

  if [[ ! -f "${ROOT_DIR}/.env" ]]; then
    cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
    echo "Creado .env desde .env.example"
  fi

  cd "${ROOT_DIR}"

  upsert_env "APP_ENV" "production"
  upsert_env "APP_DEBUG" "false"
  upsert_env "APP_URL" "$(prompt_default "APP_URL (HTTPS)" "https://chat.example.com")"
  upsert_env "APP_PORT" "$(prompt_default "APP_PORT expuesto por nginx" "8080")"
  upsert_env "LOG_LEVEL" "warning"
  upsert_env "LLM_LOG_TRAFFIC" "false"

  if env_is_empty "DB_PASSWORD" || grep -q '^DB_PASSWORD=secret$' .env 2>/dev/null; then
    local db_password
    db_password="$(prompt_secret "DB_PASSWORD (Postgres)")"
    upsert_env "DB_PASSWORD" "\"${db_password}\""
  fi

  upsert_env "DB_HOST" "postgres"

  read -r -p "¿MCP en Docker (recomendado prod)? [s/N]: " mcp_docker
  if is_yes "$mcp_docker"; then
    echo "Aviso: servicio mcp aún no está en docker-compose.yml principal."
    echo "Añádelo manualmente (ver README) o usa MCP en host."
    upsert_env "MCP_ON_HOST" "false"
    upsert_env "MCP_RPC_URL" "http://mcp:9749/rpc"
  else
    upsert_env "MCP_ON_HOST" "true"
    upsert_env "MCP_RPC_URL" "http://host.docker.internal:9749/rpc"
    upsert_env "MCP_UI_URL" "$(prompt_default "MCP_UI_URL" "http://127.0.0.1:9749")"
  fi

  configure_repos
  choose_agent_engine
  prompt_optional_git_sync

  read -r -p "¿Activar VITE_REVERB_USE_CURRENT_ORIGIN (túnel Cloudflare)? [s/N]: " reverb_origin
  if is_yes "$reverb_origin"; then
    upsert_env "VITE_REVERB_USE_CURRENT_ORIGIN" "true"
  fi

  echo ""
  echo "Levantando Docker..."
  docker compose up -d --build

  if env_is_empty "APP_KEY"; then
    docker compose exec -T app php artisan key:generate --force
  fi

  docker compose exec -T app php artisan migrate --force

  echo ""
  echo "Instalando dependencias frontend..."
  require_cmd pnpm
  pnpm install
  pnpm run build

  echo ""
  echo "=== Producción lista (base) ==="
  echo "App:  $(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"')"
  echo ""
  echo "Checklist:"
  echo "  - Reverse proxy TLS delante de puerto ${APP_PORT:-8080}"
  echo "  - MCP activo (host systemd o contenedor mcp)"
  echo "  - Repos clonados en REPOS_HOST_PATH"
  echo "  - Proyecto indexado y seleccionado en la UI"
  echo "  - No exponer Postgres a Internet"
}

main() {
  require_cmd docker
  require_cmd git

  if ! docker compose version >/dev/null 2>&1; then
    echo "Error: docker compose no disponible." >&2
    exit 1
  fi

  case "$MODE" in
    development|dev)
      setup_development
      ;;
    production|prod)
      setup_production
      ;;
    *)
      usage
      ;;
  esac
}

main "$@"
