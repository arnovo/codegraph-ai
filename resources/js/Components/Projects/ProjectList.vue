<script setup lang="ts">
import InputText from 'primevue/inputtext';
import Skeleton from 'primevue/skeleton';
import ProjectListItem from './ProjectListItem.vue';
import type { ProjectSummary } from '@/types/chat';

defineProps<{
  projects: ProjectSummary[];
  activeName: string | null;
  loading?: boolean;
}>();

const search = defineModel<string>('search', { default: '' });

const emit = defineEmits<{
  select: [name: string];
  remove: [name: string];
}>();
</script>

<template>
  <div class="flex flex-col gap-3 flex-1 min-h-0 min-w-0 overflow-hidden w-full">
    <InputText
      v-model="search"
      type="search"
      placeholder="Buscar proyecto…"
      aria-label="Buscar proyectos"
      class="w-full text-sm"
    />

    <div v-if="loading" class="flex flex-col gap-2 overflow-y-auto flex-1 min-h-0">
      <Skeleton v-for="n in 3" :key="n" height="4rem" class="w-full" />
    </div>
    <div
      v-else-if="projects.length === 0"
      class="flex flex-col items-center justify-center p-6 text-center text-gray-500 text-sm"
    >
      <i class="pi pi-folder-open text-2xl mb-2 opacity-50" />
      <p class="font-medium text-gray-700">Sin proyectos</p>
      <p class="text-xs text-gray-400">Indexa un repositorio local o desde Bitbucket</p>
    </div>
    <ul v-else class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden flex-1 min-h-0 p-0 m-0 list-none">
      <ProjectListItem
        v-for="p in projects"
        :key="p.name"
        :project="p"
        :active="p.name === activeName"
        @select="emit('select', p.name)"
        @remove="emit('remove', p.name)"
      />
    </ul>
  </div>
</template>
