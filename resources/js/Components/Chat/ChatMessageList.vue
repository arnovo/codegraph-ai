<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import Tag from 'primevue/tag';
import CodeCitationLink from './CodeCitationLink.vue';
import MarkdownBody from './MarkdownBody.vue';
import ToolCallsPanel from './ToolCallsPanel.vue';
import type { ChatMessage } from '@/types/chat';

const props = defineProps<{
  messages: ChatMessage[];
  streaming?: boolean;
  showDetails?: boolean;
}>();

const listRef = ref<HTMLElement | null>(null);

const showTyping = computed(() => {
  if (!props.streaming) return false;

  const last = props.messages[props.messages.length - 1];
  if (!last || last.role !== 'assistant') return true;

  return !last.content?.trim();
});

const scrollSignature = computed(() =>
  props.messages.map((msg) => `${msg.id ?? ''}:${msg.content?.length ?? 0}`).join('|'),
);

function shouldRenderMermaid(index: number): boolean {
  if (!props.streaming) return true;

  return index !== props.messages.length - 1;
}

function messageModelInfo(msg: ChatMessage): { model: string; label?: string } | null {
  const model = msg.metadata?.model;
  if (typeof model !== 'string' || !model.trim()) return null;

  const label = msg.metadata?.label;
  const trimmedLabel = typeof label === 'string' && label.trim() ? label.trim() : undefined;

  return { model: model.trim(), label: trimmedLabel };
}

async function scrollToBottom(behavior: 'auto' | 'smooth' = 'smooth') {
  await nextTick();
  const el = listRef.value;
  if (!el) return;

  el.scrollTo({ top: el.scrollHeight, behavior });
}

watch(
  () => [props.messages.length, scrollSignature.value, props.streaming] as const,
  () => {
    void scrollToBottom(props.streaming ? 'auto' : 'smooth');
  },
  { flush: 'post' },
);

defineExpose({ scrollToBottom });
</script>

<template>
  <div ref="listRef" class="flex flex-col gap-4 flex-1 min-h-0 overflow-y-auto p-4 bg-gray-50/50 rounded-lg">
    <div
      v-if="messages.length === 0 && !streaming"
      class="flex flex-col items-center justify-center h-full py-12 text-center text-gray-500"
    >
      <i class="pi pi-comments text-4xl mb-3 text-gray-300" />
      <p class="text-base font-semibold text-gray-700">Sin mensajes</p>
      <p class="text-xs text-gray-400">Escribe una pregunta sobre el código indexado.</p>
    </div>

    <article
      v-for="(msg, i) in messages"
      :key="msg.id ?? i"
      class="flex flex-col gap-2 p-4 rounded-xl max-w-[88%] transition-all"
      :class="
        msg.role === 'user'
          ? 'self-end bg-blue-600 text-white rounded-br-none shadow-sm'
          : 'self-start bg-white border border-gray-200 text-gray-900 rounded-bl-none shadow-sm'
      "
    >
      <header v-if="showDetails" class="flex items-center gap-2">
        <Tag
          :severity="msg.role === 'user' ? 'secondary' : 'info'"
          :value="msg.role === 'user' ? 'Tú' : 'Asistente'"
        />
      </header>

      <ToolCallsPanel
        v-if="showDetails && msg.role === 'assistant' && msg.metadata?.tools?.length"
        :tools="msg.metadata.tools"
      />

      <MarkdownBody
        v-if="msg.content"
        :content="msg.content"
        :render-mermaid="shouldRenderMermaid(i)"
        class="prose text-sm leading-relaxed"
      />
      <div
        v-else-if="msg.role === 'assistant' && streaming && i === messages.length - 1"
        class="flex items-center gap-2 text-xs text-gray-500 italic py-1"
        aria-live="polite"
      >
        <i class="pi pi-spin pi-spinner" />
        <span>Escribiendo…</span>
      </div>

      <ul v-if="showDetails && msg.metadata?.citations?.length" class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 p-0 m-0 list-none text-xs">
        <li v-for="(c, ci) in msg.metadata.citations" :key="ci">
          <CodeCitationLink :file="c.file" :line="c.line" :symbol="c.symbol" />
        </li>
      </ul>

      <footer v-if="showDetails && msg.role === 'assistant' && messageModelInfo(msg)" class="flex items-center gap-1.5 text-xs pt-1">
        <Tag v-if="messageModelInfo(msg)?.label" severity="info" :value="messageModelInfo(msg)?.label" />
        <Tag severity="secondary" :value="messageModelInfo(msg)?.model" />
      </footer>
    </article>

    <div
      v-if="showTyping && messages[messages.length - 1]?.role !== 'assistant'"
      class="flex items-center gap-2 text-xs text-gray-500 italic p-3 bg-white border border-gray-200 rounded-xl rounded-bl-none self-start shadow-sm"
      aria-live="polite"
    >
      <i class="pi pi-spin pi-spinner" />
      <span>Escribiendo…</span>
    </div>
  </div>
</template>

<style scoped>
.message-list__typing {
  display: inline-flex;
  align-items: center;
  gap: var(--prinex-spacing-sm);
  margin: 0;
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 75%, transparent));
}

.message-list__typing--standalone {
  padding: 0 var(--prinex-spacing-xs);
}

.message-list__empty {
  margin: 0;
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 65%, transparent));
  font-style: italic;
}

.message-list__citations {
  margin: var(--prinex-spacing-sm) 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--prinex-spacing-xs);
}

.message-list__model {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--prinex-spacing-xs);
  margin-top: var(--prinex-spacing-xs);
}

.message-list__model :deep(.prinex-tag) {
  font-size: var(--prinex-font-size-xs);
  line-height: 1.2;
  padding: 0.1rem 0.45rem;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
  .message-list {
    scroll-behavior: auto;
  }
}
</style>
