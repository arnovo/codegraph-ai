<script setup lang="ts">
import Button from 'primevue/button';
import PanelListItem from '@/Components/ui/PanelListItem.vue';
import type { ProjectSummary } from '@/types/chat';

defineProps<{
  project: ProjectSummary;
  active?: boolean;
}>();

const emit = defineEmits<{
  select: [];
  remove: [];
}>();
</script>

<template>
  <PanelListItem :active="active" @select="emit('select')">
    <div class="flex flex-col gap-0.5 min-w-0">
      <strong class="text-sm font-semibold text-gray-900 truncate">
        {{ project.display_name ?? project.name }}
      </strong>
      <span class="text-xs text-gray-500 truncate">{{ project.primary_stack ?? '—' }}</span>
    </div>

    <template #actions>
      <Button
        icon="pi pi-trash"
        severity="danger"
        text
        rounded
        size="small"
        aria-label="Eliminar proyecto"
        @click="emit('remove')"
      />
    </template>
  </PanelListItem>
</template>
