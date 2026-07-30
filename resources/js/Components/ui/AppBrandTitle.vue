<script setup lang="ts">
import { APP_REVISION } from '@/lib/appRevision';

withDefaults(
  defineProps<{
    label?: string;
    tag?: 'span' | 'h1';
  }>(),
  {
    label: 'CodeGraph AI',
    tag: 'span',
  },
);
</script>

<template>
  <component :is="tag" class="app-brand-title">
    {{ label }}
    <small class="app-brand-title__revision" :aria-label="`Revisión ${APP_REVISION}`">
      r{{ APP_REVISION }}
    </small>
  </component>
</template>

<style scoped>
.app-brand-title {
  font-size: inherit;
  font-weight: inherit;
  line-height: inherit;
}

.app-brand-title__revision {
  margin-inline-start: 0.35em;
  font-size: 0.62em;
  font-weight: 400;
  opacity: 0.72;
  vertical-align: baseline;
  letter-spacing: 0.02em;
}
</style>
