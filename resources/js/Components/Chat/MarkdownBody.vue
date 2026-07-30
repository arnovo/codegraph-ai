<script setup lang="ts">
import mermaid from 'mermaid';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { renderMarkdown } from '@/lib/renderMarkdown';

const props = defineProps<{
  content: string;
  renderMermaid?: boolean;
}>();

const root = ref<HTMLElement | null>(null);
let mermaidReady = false;

const html = computed(() => (props.content ? renderMarkdown(props.content) : ''));

function ensureMermaid() {
  if (mermaidReady) return;

  mermaid.initialize({
    startOnLoad: false,
    theme: 'default',
    securityLevel: 'strict',
    fontFamily: 'var(--prinex-font-family-base, system-ui, sans-serif)',
  });
  mermaidReady = true;
}

async function paintMermaid() {
  if (!props.renderMermaid || !root.value) return;

  const nodes = root.value.querySelectorAll<HTMLElement>('.markdown-body__mermaid');
  if (!nodes.length) return;

  ensureMermaid();

  await nextTick();

  try {
    await mermaid.run({ nodes: Array.from(nodes) });
  } catch {
    // Partial markdown while streaming can produce invalid diagrams.
  }
}

onMounted(() => {
  void paintMermaid();
});

watch(
  () => [props.content, props.renderMermaid] as const,
  () => {
    void paintMermaid();
  },
);
</script>

<template>
  <!-- eslint-disable-next-line vue/no-v-html -- sanitized via renderMarkdown + DOMPurify -->
  <div ref="root" class="markdown-body" v-html="html" />
</template>

<style scoped>
.markdown-body {
  line-height: 1.55;
  font-size: var(--prinex-font-size-sm);
  overflow-wrap: anywhere;
}

.markdown-body :deep(p) {
  margin: 0 0 0.65rem;
}

.markdown-body :deep(p:last-child) {
  margin-bottom: 0;
}

.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4) {
  margin: 0.85rem 0 0.45rem;
  line-height: 1.3;
  font-weight: 600;
}

.markdown-body :deep(h1) {
  font-size: 1.15rem;
}

.markdown-body :deep(h2) {
  font-size: 1.05rem;
}

.markdown-body :deep(h3),
.markdown-body :deep(h4) {
  font-size: 0.95rem;
}

.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  margin: 0.35rem 0 0.65rem;
  padding-left: 1.25rem;
}

.markdown-body :deep(li) {
  margin: 0.2rem 0;
}

.markdown-body :deep(blockquote) {
  margin: 0.5rem 0;
  padding: 0.35rem 0.75rem;
  border-left: 3px solid var(--prinex-color-primary);
  opacity: 0.9;
}

.markdown-body :deep(a) {
  color: var(--prinex-color-primary);
  text-decoration: underline;
}

.markdown-body :deep(code) {
  font-family: var(--prinex-font-family-mono, ui-monospace, monospace);
  font-size: 0.88em;
  padding: 0.1em 0.35em;
  border-radius: var(--prinex-radius-sm);
  background: var(--prinex-color-surface-muted);
}

.markdown-body :deep(pre) {
  margin: 0.55rem 0;
  padding: 0.65rem 0.75rem;
  border-radius: var(--prinex-radius-md);
  background: var(--prinex-color-surface-muted);
  border: 1px solid var(--prinex-color-border);
  overflow-x: auto;
}

.markdown-body :deep(pre code) {
  padding: 0;
  background: transparent;
}

.markdown-body :deep(table) {
  width: 100%;
  margin: 0.55rem 0;
  border-collapse: collapse;
  font-size: 0.92em;
}

.markdown-body :deep(th),
.markdown-body :deep(td) {
  border: 1px solid var(--prinex-color-border);
  padding: 0.35rem 0.5rem;
}

.markdown-body :deep(hr) {
  margin: 0.75rem 0;
  border: none;
  border-top: 1px solid var(--prinex-color-border);
}

.markdown-body :deep(.markdown-body__mermaid) {
  margin: 0.65rem 0;
  padding: 0.5rem;
  border-radius: var(--prinex-radius-md);
  background: var(--prinex-color-surface-muted);
  border: 1px solid var(--prinex-color-border);
  overflow-x: auto;
}
</style>
