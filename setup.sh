#!/usr/bin/env bash

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

echo -e "${BLUE}${BOLD}"
echo "==========================================================="
echo "        🚀 CodeGraph AI - Easy Setup"
echo "==========================================================="
echo -e "${NC}"

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker no está instalado o no se encuentra en el PATH.${NC}"
    echo "Por favor instala Docker Desktop o Docker Engine para continuar: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check Docker Compose
if ! docker compose version &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker Compose no está disponible.${NC}"
    echo "Asegúrate de tener Docker Compose v2 instado."
    exit 1
fi

echo -e "${GREEN}✓ Prerrequisitos del sistema verificados (Docker + Docker Compose).${NC}\n"

# Prepare .env file
if [ ! -f .env ]; then
    echo -e "${CYAN}Creating .env file from .env.example...${NC}"
    cp .env.example .env
fi

echo -e "${BOLD}Configuración inicial:${NC}"

# Configure LLM Provider & Key
read -p "🔑 Introduce tu API Key de LLM (ej. Gemini/OpenAI API key) [Dejar vacío para configurar más tarde]: " LLM_KEY
if [ -n "$LLM_KEY" ]; then
    if grep -q "^LLM_API_KEY=" .env; then
        sed -i.bak "s|^LLM_API_KEY=.*|LLM_API_KEY=$LLM_KEY|" .env && rm -f .env.bak
    else
        echo "LLM_API_KEY=$LLM_KEY" >> .env
    fi
fi

# Configure Repos Path
DEFAULT_REPOS_PATH="$(pwd)/repos"
read -p "📁 Ruta local donde tienes tus repositorios Git [$DEFAULT_REPOS_PATH]: " USER_REPOS_PATH
REPOS_PATH="${USER_REPOS_PATH:-$DEFAULT_REPOS_PATH}"

mkdir -p "$REPOS_PATH"

if grep -q "^REPOS_HOST_PATH=" .env; then
    sed -i.bak "s|^REPOS_HOST_PATH=.*|REPOS_HOST_PATH=$REPOS_PATH|" .env && rm -f .env.bak
else
    echo "REPOS_HOST_PATH=$REPOS_PATH" >> .env
fi

# Configure MCP RPC & UI URL
DEFAULT_USER_MCP="http://localhost:9749"
read -p "🌐 URL donde corre tu codebase-memory-mcp [$DEFAULT_USER_MCP]: " USER_MCP_INPUT
RAW_MCP_URL="${USER_MCP_INPUT:-$DEFAULT_USER_MCP}"

# Strip trailing slash or /rpc if entered by user
CLEAN_MCP_URL="$(echo "$RAW_MCP_URL" | sed 's|/rpc$||' | sed 's|/$||')"

# UI URL for browser
MCP_UI_URL="$CLEAN_MCP_URL"

# Container RPC URL (replace localhost/127.0.0.1 with host.docker.internal)
CONTAINER_RPC_URL="$(echo "$CLEAN_MCP_URL" | sed -E 's|localhost|host.docker.internal|g' | sed -E 's|127\.0\.0\.1|host.docker.internal|g')/rpc"

if grep -q "^MCP_RPC_URL=" .env; then
    sed -i.bak "s|^MCP_RPC_URL=.*|MCP_RPC_URL=$CONTAINER_RPC_URL|" .env && rm -f .env.bak
else
    echo "MCP_RPC_URL=$CONTAINER_RPC_URL" >> .env
fi

if grep -q "^MCP_UI_URL=" .env; then
    sed -i.bak "s|^MCP_UI_URL=.*|MCP_UI_URL=$MCP_UI_URL|" .env && rm -f .env.bak
else
    echo "MCP_UI_URL=$MCP_UI_URL" >> .env
fi

echo -e "\n${CYAN}📦 Levantando contenedores con Docker Compose...${NC}"
docker compose up -d

echo -e "${CYAN}⚙️  Ejecutando configuraciones de Laravel...${NC}"
docker compose exec -T app php artisan key:generate --force
docker compose exec -T app php artisan migrate --force

# Check if node/pnpm available on host to build assets
if command -v pnpm &> /dev/null; then
    echo -e "${CYAN}🎨 Compilando assets del frontend (pnpm)...${NC}"
    pnpm install --silent
    pnpm run build
fi

echo -e "\n${GREEN}${BOLD}===========================================================${NC}"
echo -e "${GREEN}${BOLD}🎉 ¡Despliegue completado con éxito!${NC}"
echo -e "${GREEN}${BOLD}===========================================================${NC}\n"
echo -e "🌐 Abre la aplicación en tu navegador: ${CYAN}${BOLD}http://localhost:8080${NC}\n"
echo -e "${YELLOW}${BOLD}📌 REQUISITO IMPORTANTE (codebase-memory-mcp):${NC}"
echo -e "Recuerda ejecutar el servidor de grafo MCP en tu máquina host:"
echo -e "   ${CYAN}npx codebase-memory-mcp --port=9749 --ui=true${NC}\n"
