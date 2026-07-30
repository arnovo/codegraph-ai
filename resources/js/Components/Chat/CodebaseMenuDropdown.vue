<script setup lang="ts">
import { computed, ref } from 'vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
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

const menu = ref();

const running = computed(() => props.status.status === 'running');

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

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    running: 'Activo',
    stopped: 'Parado',
    unhealthy: 'Degradado',
    unknown: 'Desconocido',
  };

  return labels[props.status.status] ?? props.status.status;
});

const menuItems = computed(() => [
  {
    label: 'Iniciar',
    icon: 'pi pi-play',
    disabled: running.value || props.loading,
    command: () => emit('start'),
  },
  {
    label: 'Pausar',
    icon: 'pi pi-pause',
    disabled: !running.value || props.loading,
    command: () => emit('stop'),
  },
  {
    label: 'Abrir grafo',
    icon: 'pi pi-sitemap',
    disabled: !running.value || props.loading,
    command: () => emit('openGraph'),
  },
]);

function toggleMenu(event: Event) {
  menu.value.toggle(event);
}
</script>

<template>
  <div class="inline-flex items-center gap-2">
    <Tag :severity="tagSeverity" :value="statusLabel" aria-live="polite" />
    <i v-if="loading" class="pi pi-spin pi-spinner text-gray-500" aria-label="Cargando MCP" />
    <Button
      label="Codebase"
      icon="pi pi-cog"
      icon-pos="left"
      severity="secondary"
      size="small"
      aria-haspopup="true"
      aria-controls="codebase_menu"
      @click="toggleMenu"
    />
    <Menu id="codebase_menu" ref="menu" :model="menuItems" :popup="true" />
  </div>
</template>
