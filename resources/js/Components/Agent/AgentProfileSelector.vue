<script setup lang="ts">
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import type { AgentProfileSummary } from '@/types/agent';

const open = defineModel<boolean>('open', { default: false });

defineProps<{
  profiles: AgentProfileSummary[];
  activeSlug: string;
}>();

const emit = defineEmits<{
  select: [slug: string];
}>();

function onSelect(slug: string) {
  emit('select', slug);
  open.value = false;
}
</script>

<template>
  <Dialog
    :visible="open"
    header="Perfil del agente"
    modal
    :style="{ width: '440px' }"
    @update:visible="open = $event"
  >
    <p class="text-xs text-gray-500 mb-3">Cambia cómo responde el asistente en esta sesión.</p>
    <ul class="flex flex-col gap-2 p-0 m-0 list-none">
      <li
        v-for="profile in profiles"
        :key="profile.slug"
        class="border rounded-lg overflow-hidden transition-all"
        :class="
          profile.slug === activeSlug
            ? 'border-blue-600 bg-blue-50/70'
            : 'border-gray-200 hover:border-blue-400 hover:bg-gray-50'
        "
      >
        <button
          type="button"
          class="w-full flex flex-col items-start gap-1 p-3 text-left bg-transparent border-none cursor-pointer"
          @click="onSelect(profile.slug)"
        >
          <span class="text-sm font-semibold text-gray-900">{{ profile.label }}</span>
          <span class="text-xs text-gray-500">{{ profile.description }}</span>
        </button>
      </li>
    </ul>
    <template #footer>
      <div class="flex justify-end mt-4">
        <Button label="Cerrar" severity="secondary" text @click="open = false" />
      </div>
    </template>
  </Dialog>
</template>
