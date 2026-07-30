# 🚀 CodeGraph AI

> Smart AI assistant to chat with your codebase using code graphs (**[codebase-memory-mcp](https://github.com/DeusData/codebase-memory-mcp)**), real-time streaming, multi-model fallback, and interactive project insights.

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)
![Vue](https://img.shields.io/badge/Vue-3.5-green.svg)
![PrimeVue](https://img.shields.io/badge/UI-PrimeVue-42b883.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ed.svg)

---

## 🌟 Features

- 🔍 **Code Graph Memory:** Search functions, classes, and code structures via MCP tools (`search_graph`, `get_code_snippet`, `trace_path`).
- ⚡ **Real-time SSE Streaming:** Live response streaming with citation links (`file:line`) and step-by-step tool execution feedback.
- 📁 **Local & Remote Repositories:** Index local directories or clone remote Bitbucket / Git repositories directly from the UI.
- 🔄 **Multi-Model Fallback Chain:** Configure LLM profiles (Gemini, OpenAI, Anthropic, Ollama, OpenRouter) with drag-and-drop automatic failover.
- 🎨 **Modern PrimeVue UI:** Clean, responsive interface built with PrimeVue (Aura preset) and TailwindCSS.
- 📊 **Chat Insights & BI:** Visual analytics on top questions, most queried files, tool usage, and active project distributions.
- 🤖 **Agent Profiles:** Toggle between specialized assistant modes (*Development* / *Support*).
- 💾 **Persistent History & Summaries:** Manage conversations with LLM-generated summaries and stale alerts.
- 📦 **1-Command Interactive Setup:** Zero-friction installation script (`./setup.sh`).

---

## 📋 Prerequisites

1. **Docker & Docker Compose v2** installed on your system.
2. **[codebase-memory-mcp](https://github.com/DeusData/codebase-memory-mcp)** running on your host machine (or accessible via URL).

### Install Codebase Memory MCP (Host Prerequisite)

Run the MCP graph server on your host machine:

```bash
# Install globally or via npx
npx codebase-memory-mcp --port=9749 --ui=true
```

*(This runs the JSON-RPC server at `http://localhost:9749/rpc` and the graph UI at `http://localhost:9749`)*

---

## ⚡ Quickstart (Single Command)

Clone the repository and run the setup script:

```bash
git clone git@github.com:arnovo/codegraph-ai.git
cd codegraph-ai

# Run interactive setup wizard
chmod +x setup.sh
./setup.sh
```

The installer will:
1. Copy and configure your `.env` file.
2. Ask for your preferred LLM API key and local repository directory.
3. Prompt for your local `codebase-memory-mcp` URL (defaults to `http://localhost:9749` and automatically handles Docker container network routing).
4. Launch Docker containers (Laravel, PostgreSQL, Nginx, Reverb).
5. Run database migrations and generate application keys.
6. Compile frontend assets.

Once finished, open **[http://localhost:8080](http://localhost:8080)** in your browser!

---

## 🛠️ Configuration Guide

### Environment Variables (`.env`)

Key configuration options in `.env`:

```dotenv
# App Port
APP_PORT=8080

# Local directory where your Git repositories are stored
REPOS_HOST_PATH=/path/to/your/git/repos

# External MCP Server URL (Host gateway from Docker container)
MCP_RPC_URL=http://host.docker.internal:9749/rpc
MCP_UI_URL=http://localhost:9749

# LLM Driver & API Key (Gemini / OpenAI compatible)
LLM_DRIVER=openai
LLM_API_KEY=your-api-key-here
LLM_MODEL=gemini-2.5-flash
```

---

## 🏗️ Architecture

```
User Browser
    │
    ▼ (HTTP :8080 / WS :8081)
┌──────────────────────────────────────────────────┐
│  Docker Stack                                    │
│  ┌───────────┐   ┌───────────┐   ┌────────────┐  │
│  │ Nginx     │──>│ Laravel   │──>│ PostgreSQL │  │
│  └───────────┘   └─────┬─────┘   └────────────┘  │
└────────────────────────┼─────────────────────────┘
                         │
                         ▼ (JSON-RPC)
       ┌───────────────────────────────────┐
       │ Host Machine / External Service   │
       │ codebase-memory-mcp (:9749/rpc)   │
       └───────────────────────────────────┘
```

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on code style, testing, and development workflows.

---

## 📜 License

This project is open-source and licensed under the [MIT License](LICENSE).
