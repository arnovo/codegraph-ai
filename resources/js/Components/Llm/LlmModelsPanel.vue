<script setup lang="ts">
import { ref } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import type { LlmEnvSnapshot, LlmModelProfile } from '@/types/llm';

const props = defineProps<{
  models: LlmModelProfile[];
  saving?: boolean;
  env?: LlmEnvSnapshot | null;
}>();

const emit = defineEmits<{
  'update:models': [value: LlmModelProfile[]];
  save: [];
  add: [];
  remove: [index: number];
  reorder: [fromIndex: number, toIndex: number];
}>();

const dragIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

function updateModel(index: number, patch: Partial<LlmModelProfile>) {
  const next = props.models.map((item, i) => (i === index ? { ...item, ...patch } : item));
  emit('update:models', next);
}

function onDragStart(index: number, event: DragEvent) {
  dragIndex.value = index;
  dragOverIndex.value = index;
  event.dataTransfer?.setData('text/plain', String(index));
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
  }
}

function onDragOver(index: number, event: DragEvent) {
  event.preventDefault();
  dragOverIndex.value = index;
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
}

function onDrop(index: number, event: DragEvent) {
  event.preventDefault();
  const from = dragIndex.value;
  dragIndex.value = null;
  dragOverIndex.value = null;

  if (from === null || from === index) return;
  emit('reorder', from, index);
}

function onDragEnd() {
  dragIndex.value = null;
  dragOverIndex.value = null;
}

function apiKeyPlaceholder(item: LlmModelProfile): string {
  if (item.api_key_set && item.api_key_preview) {
    return `Guardada (${item.api_key_preview}) — dejar vacío para mantener`;
  }

  return 'API key';
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <p class="text-xs text-gray-500 leading-relaxed">
      Orden = prioridad. El primero se usa; si falla (cuota, rate-limit, error), pasa al siguiente.
      La fila <strong>.env</strong> lee credenciales de <code>LLM_*</code> y también se puede reordenar.
    </p>

    <ul v-if="models.length" class="flex flex-col gap-3 p-0 m-0 list-none max-h-[58vh] overflow-y-auto" role="list">
      <li
        v-for="(item, index) in models"
        :key="item.id"
        class="flex flex-col gap-3 p-3 border rounded-lg bg-white cursor-grab transition-all"
        :class="{
          'border-blue-300 bg-blue-50/40': item.use_env_credentials,
          'opacity-50': dragIndex === index,
          'border-blue-600 -translate-y-0.5': dragOverIndex === index && dragIndex !== index,
        }"
        draggable="true"
        @dragstart="onDragStart(index, $event)"
        @dragover="onDragOver(index, $event)"
        @drop="onDrop(index, $event)"
        @dragend="onDragEnd"
      >
        <div class="flex items-center flex-wrap gap-2">
          <i class="pi pi-bars text-gray-400 cursor-grab" aria-hidden="true" />
          <span class="text-xs font-semibold text-gray-500 min-w-4 text-center">{{ index + 1 }}</span>

          <Tag v-if="item.use_env_credentials" severity="info" value="Desde .env" />
          <Tag v-else severity="secondary" value="Personalizado" />

          <div class="flex items-center gap-1.5 ml-auto">
            <Checkbox
              :model-value="item.enabled"
              binary
              :input-id="`model-enabled-${item.id}`"
              @update:model-value="updateModel(index, { enabled: Boolean($event) })"
            />
            <label :for="`model-enabled-${item.id}`" class="text-xs text-gray-700 cursor-pointer">Activo</label>
          </div>

          <Button
            v-if="!item.use_env_credentials"
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            size="small"
            aria-label="Eliminar modelo"
            @click="emit('remove', index)"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div class="flex flex-col gap-1 min-w-0">
            <label class="text-xs font-semibold text-gray-700">Modelo</label>
            <InputText
              :model-value="item.model"
              :disabled="item.use_env_credentials"
              placeholder="gemini-3.5-flash-lite"
              class="w-full text-xs"
              @update:model-value="updateModel(index, { model: String($event ?? '') })"
            />
          </div>

          <div class="flex flex-col gap-1 min-w-0">
            <label class="text-xs font-semibold text-gray-700">Endpoint</label>
            <InputText
              :model-value="item.base_url ?? ''"
              :disabled="item.use_env_credentials"
              placeholder="https://generativelanguage.googleapis.com/v1beta/openai"
              class="w-full text-xs"
              @update:model-value="updateModel(index, { base_url: String($event ?? '') || null })"
            />
          </div>

          <div class="flex flex-col gap-1 min-w-0">
            <label class="text-xs font-semibold text-gray-700">API key</label>
            <InputText
              v-if="item.use_env_credentials"
              :model-value="item.api_key_preview ?? ''"
              disabled
              type="password"
              class="w-full text-xs"
            />
            <InputText
              v-else
              :model-value="item.api_key ?? ''"
              type="password"
              :placeholder="apiKeyPlaceholder(item)"
              autocomplete="off"
              class="w-full text-xs"
              @update:model-value="updateModel(index, { api_key: String($event ?? '') })"
            />
          </div>

          <div class="flex flex-col gap-1 min-w-0">
            <label class="text-xs font-semibold text-gray-700">Etiqueta</label>
            <InputText
              :model-value="item.label ?? ''"
              :placeholder="item.use_env_credentials ? 'Principal (.env)' : 'Opcional'"
              class="w-full text-xs"
              @update:model-value="updateModel(index, { label: String($event ?? '') || null })"
            />
          </div>
        </div>
      </li>
    </ul>

    <p v-else class="text-xs text-gray-500">Sin modelos configurados.</p>

    <div class="flex justify-between items-center gap-2 mt-1">
      <Button label="+ Añadir modelo" severity="secondary" size="small" @click="emit('add')" />
      <Button
        :label="saving ? 'Guardando…' : 'Guardar cadena'"
        size="small"
        :disabled="saving || !models.length"
        @click="emit('save')"
      />
    </div>
  </div>
</template>
