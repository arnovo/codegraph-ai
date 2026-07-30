import { computed, ref, watch } from 'vue';
import type { AgentProfileSummary } from '@/types/agent';
import { readStoredString, writeStoredString } from '@/lib/storedString';

const STORAGE_KEY = 'chat.agentProfile';

export function useAgentProfiles(profiles: AgentProfileSummary[]) {
  const defaultSlug = profiles.find((profile) => profile.is_default)?.slug ?? profiles[0]?.slug ?? 'developer';
  const activeSlug = ref(readStoredString(STORAGE_KEY, defaultSlug));

  watch(activeSlug, (value) => writeStoredString(STORAGE_KEY, value));

  const activeProfile = computed(() => {
    return profiles.find((profile) => profile.slug === activeSlug.value) ?? profiles[0] ?? null;
  });

  function select(slug: string) {
    if (!profiles.some((profile) => profile.slug === slug)) {
      return;
    }

    activeSlug.value = slug;
  }

  return { profiles, activeSlug, activeProfile, select };
}
