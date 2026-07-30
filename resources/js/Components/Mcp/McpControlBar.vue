<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import type { McpStatus } from '@/types/chat';

const props = defineProps<{
  status: McpStatus;
  loading?: boolean;
}>();

const emit = defineEmits<{
  start: [];
  stop: [];
  openGraph: [];
}>();

const tagSeverity = computed(() => {
  switch (props.status.status) {
    case 'running':
      return 'success';
    case 'unhealthy':
      return 'warn';
    case 'stopped':
      return 'danger';
    default:
      return 'secondary';
  }
});

const label = computed(() => {
  const map: Record<string, string> = {
    running: 'MCP activo',
    stopped: 'MCP parado',
    unhealthy: 'MCP degradado',
    unknown: 'MCP desconocido',
  };
  return map[props.status.status] ?? props.status.status;
});

const running = computed(() => props.status.status === 'running');
</script>

<template>
  <div class="flex items-center flex-wrap gap-2">
    <Tag :severity="tagSeverity" :value="label" aria-live="polite" />
    <i v-if="loading" class="pi pi-spin pi-spinner text-gray-500" aria-label="Cargando MCP" />
    <Button
      label="Levantar codebase"
      icon="pi pi-play"
      severity="secondary"
      size="small"
      :loading="loading"
      @click="emit('start')"
    />
    <Button
      label="Parar codebase"
      icon="pi pi-power-off"
      severity="secondary"
      size="small"
      :disabled="loading || !running"
      @click="emit('stop')"
    />
    <Button
      label="Abrir grafo"
      icon="pi pi-sitemap"
      severity="secondary"
      size="small"
      :disabled="loading || !running"
      @click="emit('openGraph')"
    />
  </div>
</template>
