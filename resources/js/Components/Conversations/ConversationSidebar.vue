<script setup lang="ts">
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { isSummaryStale } from '@/lib/conversationSummary';
import type { ConversationSummary } from '@/types/chat';

defineProps<{
  conversations: ConversationSummary[];
  activeId: string | null;
  summarizingId?: string | null;
}>();

const search = defineModel<string>('search', { default: '' });

const emit = defineEmits<{
  select: [id: string];
  rename: [id: string];
  remove: [id: string];
  summarize: [id: string];
  viewSummary: [id: string];
}>();
</script>

<template>
  <div class="flex flex-col gap-3 flex-1 min-h-0 min-w-0 overflow-hidden w-full">
    <InputText
      v-model="search"
      type="search"
      placeholder="Buscar historial…"
      aria-label="Buscar conversaciones"
      class="w-full text-sm"
    />
    <div
      v-if="conversations.length === 0"
      class="flex flex-col items-center justify-center p-6 text-center text-gray-500 text-sm"
    >
      <i class="pi pi-comments text-2xl mb-2 opacity-50" />
      <p class="font-medium text-gray-700">Sin conversaciones</p>
      <p class="text-xs text-gray-400">Inicia un nuevo chat para comenzar</p>
    </div>
    <ul v-else class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden flex-1 min-h-0 p-0 m-0 list-none">
      <li
        v-for="conversation in conversations"
        :key="conversation.id"
        class="relative p-3 border border-gray-200 rounded-lg bg-white cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50/50"
        :class="{ 'border-blue-600 bg-blue-50 shadow-sm': conversation.id === activeId }"
        :aria-current="conversation.id === activeId ? 'true' : undefined"
        @click="emit('select', conversation.id)"
      >
        <span
          v-if="conversation.id === activeId"
          class="absolute left-1 top-3 bottom-3 w-1 bg-blue-600 rounded-full"
          aria-hidden="true"
        />
        <div class="flex flex-col gap-2 min-w-0">
          <span class="text-sm font-medium text-gray-900 truncate">
            {{ conversation.title }}
          </span>
          <div class="flex items-center justify-end gap-1 flex-wrap ml-auto">
            <Button
              icon="pi pi-file-edit"
              severity="secondary"
              text
              rounded
              size="small"
              :loading="summarizingId === conversation.id"
              :aria-label="conversation.summary ? 'Regenerar resumen' : 'Generar resumen'"
              @click.stop="emit('summarize', conversation.id)"
            />
            <Button
              v-if="conversation.summary"
              label="Resumen"
              :severity="isSummaryStale(conversation) ? 'warn' : 'secondary'"
              text
              size="small"
              class="text-xs py-0.5 px-2"
              aria-label="Ver resumen"
              @click.stop="emit('viewSummary', conversation.id)"
            />
            <Button
              icon="pi pi-pencil"
              severity="secondary"
              text
              rounded
              size="small"
              aria-label="Renombrar"
              @click.stop="emit('rename', conversation.id)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              aria-label="Eliminar"
              @click.stop="emit('remove', conversation.id)"
            />
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
