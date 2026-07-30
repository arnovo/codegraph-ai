import { ref, onMounted, onUnmounted } from 'vue';
import type { McpStatus } from '@/types/chat';
import { apiFetch } from '@/lib/api';
import { buildMcpGraphUrl } from '@/lib/mcpGraphUrl';
import { subscribeMcpStatus, bindEchoConnectionHandlers } from '@/lib/echo';

const MCP_STATUS_POLL_MS = 15_000;

export function useMcpStatus(initial: McpStatus) {
  const status = ref<McpStatus>(initial);
  const loading = ref(false);
  const connected = ref(false);
  let unsubscribe: (() => void) | null = null;
  let unbindConnection: (() => void) | null = null;
  let pollTimer: ReturnType<typeof setInterval> | null = null;

  function startPollingFallback() {
    if (pollTimer) return;

    pollTimer = setInterval(() => {
      if (!connected.value) {
        void refresh();
      }
    }, MCP_STATUS_POLL_MS);
  }

  function stopPollingFallback() {
    if (!pollTimer) return;

    clearInterval(pollTimer);
    pollTimer = null;
  }

  async function refresh() {
    try {
      const res = await apiFetch('/mcp/status');
      if (res.ok) status.value = await res.json();
    } catch {
      /* ignore refresh errors */
    }
  }

  async function start() {
    loading.value = true;
    try {
      const res = await apiFetch('/mcp/start', { method: 'POST', body: '{}' });
      if (res.ok) status.value = await res.json();
    } finally {
      loading.value = false;
    }
  }

  async function stop() {
    loading.value = true;
    try {
      const res = await apiFetch('/mcp/stop', { method: 'POST', body: '{}' });
      if (res.ok) status.value = await res.json();
    } finally {
      loading.value = false;
    }
  }

  function openGraph(projectName?: string | null) {
    if (!status.value.ui_url) {
      return;
    }

    const url = buildMcpGraphUrl(status.value.ui_url, projectName);
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  onMounted(() => {
    try {
      unsubscribe = subscribeMcpStatus((payload) => {
        connected.value = true;
        status.value = payload;
        stopPollingFallback();
      });

      unbindConnection = bindEchoConnectionHandlers(
        () => {
          connected.value = true;
          stopPollingFallback();
        },
        () => {
          connected.value = false;
          startPollingFallback();
          void refresh();
        },
      );

      startPollingFallback();
      window.setTimeout(() => {
        if (!connected.value) {
          void refresh();
        }
      }, 3_000);
    } catch {
      connected.value = false;
      startPollingFallback();
      void refresh();
    }
  });

  onUnmounted(() => {
    unsubscribe?.();
    unbindConnection?.();
    stopPollingFallback();
  });

  return { status, loading, connected, refresh, start, stop, openGraph };
}
