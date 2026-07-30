# AGENTS.md — CodeGraph AI

Onboarding document for **AI coding assistants** (Gemini Antigravity, Cursor, Claude Code, etc.) contributing to this repository.

---

## Project Summary

**CodeGraph AI** is an open-source monolith built with **Laravel 13 + Inertia / Vue 3 + PrimeVue 4 + PostgreSQL (Docker)**. It allows users to chat with indexed Git repositories via **[codebase-memory-mcp](https://github.com/DeusData/codebase-memory-mcp)** (code graph).

Features include SSE streaming responses, MCP graph tool calls (`search_graph`, `get_code_snippet`, `trace_path`), persistent chat history with automatic summaries, and multi-model fallback profile management.

---

## Quickstart & Commands

```bash
# 1-Command Setup Script (Docker + env + migrations + asset build)
./setup.sh

# Run PHPUnit backend tests (SQLite in-memory)
docker compose exec app php artisan test

# Frontend commands
pnpm typecheck    # vue-tsc
pnpm lint         # ESLint flat config
pnpm run build    # Vite build
```

---

## Architecture Overview

```
app/
  Domains/
    Mcp/       → RPC/CLI client, tool result caching, process manager
    Projects/  → Indexed projects catalog, index/delete, Bitbucket clone
    Chat/      → Conversations, messages, streaming
    Agent/     → LLM driver clients, tool execution, prompts, fallback chain
    Internal/  → Spec & project metrics services

resources/js/
  Pages/Chat/Index.vue              → 3-panel chat layout (Projects, Main Chat, History)
  Components/Projects/AddProjectModal.vue → Bitbucket clone & local directory index modal
  Components/Chat|Conversations|Mcp|Llm|ui/
  composables/                      → useChatStream, useProjects, useMcpStatus, etc.
```

- **UI System**: PrimeVue 4 (`Aura` preset) + TailwindCSS.
- **State Management**: Props + Vue Composition API Composables + `localStorage`.

---

## Code & Testing Conventions

- **Language**: English for code, tests, docstrings, and git commits. Spanish for user-facing UI text.
- **Backend Testing**: SQLite in-memory with `DatabaseTransactions` (`Tests\TestCase`). Never use `RefreshDatabase` or `migrate:fresh`.
- **Frontend**: Component imports using `@/` path alias (e.g. `import ChatInput from '@/Components/Chat/ChatInput.vue'`). Vue 3 `<script setup lang="ts">`.
