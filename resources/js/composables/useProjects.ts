import { ref, computed, onMounted } from 'vue';
import type { ProjectSummary } from '@/types/chat';
import { apiFetch } from '@/lib/api';

export function useProjects(initial: ProjectSummary[]) {
  const projects = ref<ProjectSummary[]>(initial);
  const search = ref('');
  const activeName = ref<string | null>(null);
  const loading = ref(false);
  const refreshing = ref(false);

  const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return projects.value;
    return projects.value.filter((p) => {
      const label = (p.display_name ?? p.name).toLowerCase();
      const stack = (p.primary_stack ?? '').toLowerCase();
      return label.includes(q) || stack.includes(q) || p.name.toLowerCase().includes(q);
    });
  });

  async function refresh(options?: { background?: boolean }) {
    const background = options?.background ?? false;
    if (background) {
      if (refreshing.value) return;
      refreshing.value = true;
    } else {
      loading.value = true;
    }

    try {
      const res = await apiFetch('/projects');
      if (res.ok) projects.value = await res.json();
    } finally {
      loading.value = false;
      refreshing.value = false;
    }
  }

  async function indexRepo(repoPath: string) {
    loading.value = true;
    try {
      const res = await apiFetch('/projects/index', {
        method: 'POST',
        body: JSON.stringify({ repo_path: repoPath }),
      });
      if (res.ok) {
        await refresh();
        return true;
      }
      return false;
    } finally {
      loading.value = false;
    }
  }

  async function cloneFromBitbucket(payload: {
    repository_url: string;
    username: string;
    api_token: string;
  }): Promise<{ ok: boolean; message?: string }> {
    loading.value = true;
    try {
      const res = await apiFetch('/projects/clone', {
        method: 'POST',
        body: JSON.stringify(payload),
      });

      if (res.ok) {
        await refresh();
        return { ok: true };
      }

      const data = (await res.json().catch(() => ({}))) as { message?: string };
      return { ok: false, message: data.message ?? 'No se pudo clonar el repositorio.' };
    } finally {
      loading.value = false;
    }
  }

  async function remove(name: string) {
    const res = await apiFetch(`/projects/${encodeURIComponent(name)}`, { method: 'DELETE' });
    if (res.ok) {
      if (activeName.value === name) activeName.value = null;
      await refresh();
    }
  }

  function select(name: string) {
    activeName.value = activeName.value === name ? null : name;
  }

  function setActive(name: string | null) {
    activeName.value = name;
  }

  onMounted(() => {
    void refresh({ background: true });
  });

  return {
    projects,
    search,
    activeName,
    filtered,
    loading,
    refreshing,
    refresh,
    indexRepo,
    cloneFromBitbucket,
    remove,
    select,
    setActive,
  };
}
