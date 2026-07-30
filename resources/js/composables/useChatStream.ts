import { ref } from 'vue';
import type { ChatMessage, StreamChunk } from '@/types/chat';
import { apiFetch } from '@/lib/api';

export function useChatStream() {
  const messages = ref<ChatMessage[]>([]);
  const streaming = ref(false);
  const error = ref<string | null>(null);

  function streamingAssistant(): ChatMessage {
    const last = messages.value[messages.value.length - 1];
    if (last?.role !== 'assistant') {
      throw new Error('Missing streaming assistant message');
    }

    last.metadata ??= { tools: [] };
    last.metadata.tools ??= [];

    return last;
  }

  async function loadMessages(conversationId: string) {
    if (streaming.value) {
      return;
    }

    const res = await apiFetch(`/conversations/${conversationId}/messages`);
    if (res.ok) {
      messages.value = await res.json();
    } else {
      messages.value = [];
    }
  }

  async function send(
    conversationId: string,
    text: string,
    activeProjectName?: string | null,
    agentProfileSlug?: string | null,
  ) {
    if (!text.trim() || streaming.value) return;

    error.value = null;
    streaming.value = true;
    messages.value.push({ role: 'user', content: text });
    messages.value.push({ role: 'assistant', content: '', metadata: { tools: [] } });

    try {
      const res = await apiFetch(`/chat/${conversationId}/stream`, {
        method: 'POST',
        body: JSON.stringify({
          message: text,
          active_project_name: activeProjectName ?? undefined,
          agent_profile: agentProfileSlug ?? undefined,
        }),
      });

      if (!res.ok || !res.body) {
        throw new Error(`Error ${res.status}`);
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() ?? '';

        for (const line of lines) {
          if (!line.startsWith('data:')) continue;
          const payload = line.slice(5).trim();
          if (!payload) continue;

          let chunk: StreamChunk;
          try {
            chunk = JSON.parse(payload);
          } catch {
            continue;
          }

          const assistant = streamingAssistant();

          if (chunk.type === 'token' && chunk.content) {
            assistant.content += chunk.content;
          } else if (chunk.type === 'tool' && chunk.meta) {
            assistant.metadata ??= { tools: [] };
            assistant.metadata.tools ??= [];
            assistant.metadata.tools.push({
              name: String(chunk.meta.name ?? ''),
              arguments: chunk.meta.arguments as Record<string, unknown>,
            });
          } else if (chunk.type === 'done' && chunk.meta) {
            assistant.metadata = {
              ...assistant.metadata,
              tools: assistant.metadata?.tools ?? [],
              ...chunk.meta,
            };
          } else if (chunk.type === 'error') {
            error.value = chunk.content ?? 'Error en streaming';
            assistant.content = error.value;
          }
        }
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Error de conexión';
      const assistant = streamingAssistant();
      assistant.content = error.value;
    } finally {
      streaming.value = false;

      try {
        await loadMessages(conversationId);
      } catch {
        /* keep optimistic messages if reload fails */
      }
    }
  }

  return { messages, streaming, error, loadMessages, send };
}
