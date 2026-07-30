<script setup lang="ts">
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
  defaultLocalPath?: string;
  loading?: boolean;
}>();

const emit = defineEmits<{
  cloneBitbucket: [payload: { repository_url: string; username: string; api_token: string }];
  indexLocal: [repoPath: string];
}>();

const mode = ref<'bitbucket' | 'local'>('bitbucket');
const repositoryUrl = ref('https://bitbucket.org/workspace/repositorio');
const username = ref('');
const apiToken = ref('');
const localPath = ref(props.defaultLocalPath ?? '');

const modeOptions = [
  { label: 'Bitbucket', value: 'bitbucket' },
  { label: 'Ruta local', value: 'local' },
];

function resetForm() {
  repositoryUrl.value = 'https://bitbucket.org/workspace/repositorio';
  username.value = '';
  apiToken.value = '';
  localPath.value = props.defaultLocalPath ?? '';
  mode.value = 'bitbucket';
}

function onOpenChange(value: boolean) {
  if (!value) {
    resetForm();
  }
}

function onClose() {
  open.value = false;
  resetForm();
}

function onSubmit() {
  if (mode.value === 'bitbucket') {
    emit('cloneBitbucket', {
      repository_url: repositoryUrl.value.trim(),
      username: username.value.trim(),
      api_token: apiToken.value,
    });
    return;
  }

  emit('indexLocal', localPath.value.trim());
}
</script>

<template>
  <Dialog
    :visible="open"
    header="Nuevo proyecto"
    modal
    :style="{ width: '480px' }"
    @update:visible="onOpenChange"
  >
    <div class="mb-4">
      <SelectButton
        v-model="mode"
        :options="modeOptions"
        option-label="label"
        option-value="value"
        class="w-full"
      />
    </div>

    <div v-if="mode === 'bitbucket'" class="flex flex-col gap-3">
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-gray-700">URL del repositorio</label>
        <InputText
          v-model="repositoryUrl"
          placeholder="https://bitbucket.org/workspace/repositorio"
          class="w-full"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-gray-700">Usuario Bitbucket</label>
        <InputText v-model="username" placeholder="tu.usuario" class="w-full" />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-gray-700">App Password / token</label>
        <InputText
          v-model="apiToken"
          type="password"
          placeholder="Token de aplicación"
          class="w-full"
        />
      </div>
      <p class="text-xs text-gray-500 mt-1">
        Se clona bajo la carpeta de repos configurada y se indexa automáticamente.
        Las credenciales no se guardan en el servidor.
      </p>
    </div>

    <div v-else class="flex flex-col gap-3">
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-gray-700">Ruta absoluta bajo repos</label>
        <InputText v-model="localPath" placeholder="/opt/repos/mi-proyecto" class="w-full" />
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2 mt-4">
        <Button label="Cancelar" severity="secondary" text @click="onClose" />
        <Button :loading="loading" @click="onSubmit">
          {{ mode === 'bitbucket' ? 'Clonar e indexar' : 'Indexar' }}
        </Button>
      </div>
    </template>
  </Dialog>
</template>
