import { ref } from 'vue';
import { apiFetch } from '@/lib/api';
import type { LlmEnvSnapshot, LlmModelProfile } from '@/types/llm';

export function useLlmModels(initial: LlmModelProfile[] = [], env: LlmEnvSnapshot | null = null) {
  const models = ref<LlmModelProfile[]>([...initial]);
  const envDefaults = ref<LlmEnvSnapshot | null>(env);
  const saving = ref(false);
  const error = ref<string | null>(null);
  const removedIds = ref<string[]>([]);

  async function refresh() {
    const res = await apiFetch('/llm/models');
    if (!res.ok) {
      error.value = `Error ${res.status}`;
      return;
    }

    const data = await res.json();
    models.value = data.models ?? [];
    envDefaults.value = data.env ?? envDefaults.value;
  }

  async function save(nextModels: LlmModelProfile[]) {
    saving.value = true;
    error.value = null;

    try {
      const res = await apiFetch('/llm/models', {
        method: 'PUT',
        body: JSON.stringify({
          models: nextModels.map((item, index) => ({
            id: item.id.startsWith('tmp-') ? null : item.id,
            model: item.model.trim(),
            label: item.label?.trim() || null,
            enabled: item.enabled,
            use_env_credentials: item.use_env_credentials,
            base_url: item.use_env_credentials ? null : item.base_url?.trim() || null,
            api_key: item.use_env_credentials ? null : item.api_key?.trim() || null,
            sort_order: index,
          })),
          removed_ids: removedIds.value,
        }),
      });

      if (!res.ok) {
        throw new Error(`Error ${res.status}`);
      }

      const data = await res.json();
      models.value = (data.models ?? nextModels).map((item: LlmModelProfile) => ({
        ...item,
        api_key: '',
      }));
      removedIds.value = [];
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'No se pudo guardar';
      throw e;
    } finally {
      saving.value = false;
    }
  }

  function addModel() {
    models.value.push({
      id: `tmp-${crypto.randomUUID()}`,
      model: '',
      label: null,
      base_url: envDefaults.value?.base_url ?? '',
      api_key: '',
      sort_order: models.value.length,
      enabled: true,
      use_env_credentials: false,
      api_key_preview: null,
      api_key_set: false,
    });
  }

  function removeModel(index: number) {
    const item = models.value[index];
    if (item?.use_env_credentials) return;

    if (item?.id && !item.id.startsWith('tmp-')) {
      removedIds.value.push(item.id);
    }

    models.value.splice(index, 1);
  }

  function reorder(fromIndex: number, toIndex: number) {
    if (fromIndex === toIndex) return;

    const items = [...models.value];
    const [moved] = items.splice(fromIndex, 1);
    if (!moved) return;

    items.splice(toIndex, 0, moved);
    models.value = items.map((item, index) => ({ ...item, sort_order: index }));
  }

  return {
    models,
    envDefaults,
    saving,
    error,
    refresh,
    save,
    addModel,
    removeModel,
    reorder,
  };
}
