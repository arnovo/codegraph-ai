<script setup lang="ts">
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

withDefaults(
  defineProps<{
    open: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    danger?: boolean;
  }>(),
  {
    description: undefined,
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    danger: false,
  },
);

const emit = defineEmits<{
  'update:open': [value: boolean];
  confirm: [];
}>();

function close() {
  emit('update:open', false);
}

function confirm() {
  emit('confirm');
}
</script>

<template>
  <Dialog
    :visible="open"
    :header="title"
    modal
    :style="{ width: '400px' }"
    @update:visible="emit('update:open', $event)"
  >
    <p v-if="description" class="text-sm text-gray-600 mb-4">{{ description }}</p>
    <slot />
    <template #footer>
      <div class="flex justify-end gap-2 mt-4">
        <Button label="cancelLabel" severity="secondary" text @click="close">
          {{ cancelLabel }}
        </Button>
        <Button :severity="danger ? 'danger' : 'primary'" @click="confirm">
          {{ confirmLabel }}
        </Button>
      </div>
    </template>
  </Dialog>
</template>
