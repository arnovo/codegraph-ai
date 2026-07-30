import { ref } from 'vue';
import type { ChatInsightsData } from '@/types/chat';
import { apiFetch } from '@/lib/api';

export function useChatInsights() {
  const data = ref<ChatInsightsData | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function refresh(projectName: string | null) {
    loading.value = true;
    error.value = null;

    try {
      const query = projectName ? `?project=${encodeURIComponent(projectName)}` : '';
      const response = await apiFetch(`/chat/insights${query}`);

      if (!response.ok) {
        error.value = 'No se pudieron cargar los insights.';
        return;
      }

      data.value = await response.json() as ChatInsightsData;
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, refresh };
}
