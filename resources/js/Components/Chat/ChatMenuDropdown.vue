<script setup lang="ts">
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';

const props = defineProps<{
  activeProfileLabel: string;
  showMessageDetails: boolean;
}>();

const emit = defineEmits<{
  openModels: [];
  openProfile: [];
  toggleMetadata: [];
}>();

const menu = ref();

const menuItems = computed(() => [
  {
    label: 'Modelos LLM',
    icon: 'pi pi-sliders-h',
    command: () => emit('openModels'),
  },
  {
    label: `Perfil: ${props.activeProfileLabel}`,
    icon: 'pi pi-user',
    command: () => emit('openProfile'),
  },
  {
    label: props.showMessageDetails ? 'Ocultar metadatos' : 'Mostrar metadatos',
    icon: props.showMessageDetails ? 'pi pi-eye-slash' : 'pi pi-eye',
    command: () => emit('toggleMetadata'),
  },
]);

function toggleMenu(event: Event) {
  menu.value.toggle(event);
}
</script>

<template>
  <div class="inline-block">
    <Button
      label="Chat"
      icon="pi pi-cog"
      severity="secondary"
      size="small"
      aria-haspopup="true"
      aria-controls="chat_menu"
      @click="toggleMenu"
    />
    <Menu id="chat_menu" ref="menu" :model="menuItems" :popup="true" />
  </div>
</template>
