<script setup lang="ts">
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

defineProps<{
  open: boolean;
  title: string;
  description?: string;
  label?: string;
  modelValue: string;
  confirmLabel?: string;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'update:modelValue': [value: string];
  confirm: [];
}>();
</script>

<template>
  <Dialog
    :visible="open"
    :header="title"
    modal
    :style="{ width: '400px' }"
    @update:visible="emit('update:open', $event)"
  >
    <p v-if="description" class="text-sm text-gray-600 mb-3">{{ description }}</p>
    <div class="flex flex-col gap-1 mb-4">
      <label class="text-xs font-semibold text-gray-700">{{ label ?? 'Valor' }}</label>
      <InputText
        :model-value="modelValue"
        class="w-full"
        @update:model-value="emit('update:modelValue', String($event ?? ''))"
        @keydown.enter.prevent="emit('confirm')"
      />
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Cancelar" severity="secondary" text @click="emit('update:open', false)" />
        <Button :disabled="!modelValue.trim()" @click="emit('confirm')">
          {{ confirmLabel ?? 'Guardar' }}
        </Button>
      </div>
    </template>
  </Dialog>
</template>
