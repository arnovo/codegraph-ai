<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { readStoredBoolean, writeStoredBoolean } from '@/lib/storedBoolean';

defineProps<{
  disabled?: boolean;
  projectLabel?: string | null;
  projectStack?: string | null;
}>();

const emit = defineEmits<{
  send: [text: string];
}>();

const ENTER_TO_SEND_KEY = 'chat.enterToSend';

const text = ref('');
const enterToSend = ref(readStoredBoolean(ENTER_TO_SEND_KEY, true));

watch(enterToSend, (value) => writeStoredBoolean(ENTER_TO_SEND_KEY, value));

const suggestions = [
  '¿Qué hace este módulo?',
  '¿Quién llama a esta función?',
  'Traza el flujo desde el controlador',
];

function submit() {
  if (!text.value.trim()) return;
  emit('send', text.value);
  text.value = '';
}

function onKeydown(e: KeyboardEvent) {
  if (e.key !== 'Enter' || e.shiftKey) return;

  if (enterToSend.value) {
    e.preventDefault();
    submit();
    return;
  }

  if (e.metaKey || e.ctrlKey) {
    e.preventDefault();
    submit();
  }
}
</script>

<template>
  <div class="flex flex-col gap-3 pt-3 border-t border-gray-200">
    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="s in suggestions"
        :key="s"
        type="button"
        class="bg-transparent border-none p-0 cursor-pointer"
        @click="text = s"
      >
        <Tag severity="secondary" :value="s" class="hover:bg-gray-200 transition-colors" />
      </button>
    </div>

    <div class="flex items-center gap-1.5 flex-wrap">
      <template v-if="projectLabel">
        <Tag severity="primary" :value="projectLabel" />
        <Tag v-if="projectStack" severity="secondary" :value="projectStack" />
      </template>
      <Tag v-else severity="warn" value="Sin proyecto — elige uno en el panel izquierdo" />
    </div>

    <div class="flex flex-col gap-1">
      <Textarea
        v-model="text"
        :disabled="disabled"
        :rows="3"
        auto-resize
        :placeholder="
          enterToSend
            ? 'Pregunta sobre el código… (Enter envía)'
            : 'Pregunta sobre el código… (⌘/Ctrl+Enter envía)'
        "
        class="w-full text-sm"
        @keydown="onKeydown"
      />
    </div>

    <div class="flex items-center justify-between gap-2 flex-wrap">
      <div v-if="$slots['actions-start']" class="flex items-center gap-2">
        <slot name="actions-start" />
      </div>
      <div class="flex items-center gap-3 ml-auto">
        <div class="flex items-center gap-2 text-xs text-gray-600">
          <span>Enter envía</span>
          <ToggleSwitch v-model="enterToSend" aria-label="Enter envía" />
        </div>
        <Button label="Enviar" icon="pi pi-send" :disabled="disabled || !text.trim()" @click="submit" />
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
