import { ref } from 'vue';
import type { ConversationSummary } from '@/types/chat';
import { apiFetch } from '@/lib/api';

export function useConversations(initial: ConversationSummary[]) {
  const normalizeConversation = (conversation: ConversationSummary): ConversationSummary => ({
    ...conversation,
    summary: conversation.summary ?? null,
    summary_message_count: conversation.summary_message_count ?? null,
    messages_count: conversation.messages_count ?? 0,
  });

  const items = ref<ConversationSummary[]>(initial.map(normalizeConversation));
  const activeId = ref<string | null>(null);
  const search = ref('');

  function filtered() {
    const q = search.value.trim().toLowerCase();
    if (!q) return items.value;
    return items.value.filter((c) => c.title.toLowerCase().includes(q));
  }

  async function refresh() {
    const res = await apiFetch('/conversations');
    if (res.ok) {
      const payload = await res.json();
      items.value = payload.map((conversation: ConversationSummary) => normalizeConversation(conversation));
    }
  }

  async function create(title?: string, primaryProjectName?: string | null) {
    const res = await apiFetch('/conversations', {
      method: 'POST',
      body: JSON.stringify({
        title: title ?? 'Nueva conversación',
        primary_project_name: primaryProjectName ?? null,
      }),
    });
    if (!res.ok) return null;
    const conv = await res.json();
    const normalized: ConversationSummary = {
      ...conv,
      summary: conv.summary ?? null,
      summary_message_count: conv.summary_message_count ?? null,
      messages_count: conv.messages_count ?? 0,
      updated_at: conv.updated_at ?? null,
    };
    items.value.unshift(normalized);
    activeId.value = normalized.id;
    return normalized;
  }

  async function rename(id: string, title: string) {
    const res = await apiFetch(`/conversations/${id}`, {
      method: 'PATCH',
      body: JSON.stringify({ title }),
    });
    if (res.ok) {
      const idx = items.value.findIndex((c) => c.id === id);
      if (idx >= 0) {
        items.value[idx] = { ...items.value[idx], title };
      }
    }
  }

  async function remove(id: string) {
    const res = await apiFetch(`/conversations/${id}`, { method: 'DELETE' });
    if (res.ok) {
      if (activeId.value === id) activeId.value = null;
      await refresh();
    }
  }

  function select(id: string) {
    activeId.value = id;
  }

  function deselect() {
    activeId.value = null;
  }

  function patchConversation(id: string, patch: Partial<ConversationSummary>) {
    const idx = items.value.findIndex((conversation) => conversation.id === id);
    if (idx < 0) return;

    items.value[idx] = { ...items.value[idx], ...patch };
  }

  function bumpMessagesCount(id: string, delta = 1) {
    const idx = items.value.findIndex((conversation) => conversation.id === id);
    if (idx < 0) return;

    items.value[idx] = {
      ...items.value[idx],
      messages_count: Math.max(0, items.value[idx].messages_count + delta),
    };
  }

  async function generateSummary(id: string): Promise<ConversationSummary | null> {
    const res = await apiFetch(`/conversations/${id}/summary`, { method: 'POST' });
    if (!res.ok) return null;

    const data = await res.json();
    patchConversation(id, {
      summary: data.summary ?? null,
      summary_message_count: data.summary_message_count ?? null,
      messages_count: data.messages_count ?? items.value.find((c) => c.id === id)?.messages_count ?? 0,
    });

    return items.value.find((conversation) => conversation.id === id) ?? null;
  }

  return {
    items,
    activeId,
    search,
    filtered,
    refresh,
    create,
    rename,
    remove,
    select,
    deselect,
    patchConversation,
    bumpMessagesCount,
    generateSummary,
  };
}
