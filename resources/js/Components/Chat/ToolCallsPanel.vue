<script setup lang="ts">
import { ref } from 'vue';
import Tag from 'primevue/tag';
import Popover from 'primevue/popover';

defineProps<{
  tools: Array<{ name: string; arguments?: Record<string, unknown>; result_summary?: string }>;
}>();

const opRefs = ref<Record<number, any>>({});

function togglePopover(event: Event, index: number) {
  if (opRefs.value[index]) {
    opRefs.value[index].toggle(event);
  }
}

function setOpRef(el: any, index: number) {
  if (el) {
    opRefs.value[index] = el;
  }
}

function formatArgs(args?: Record<string, unknown>): string {
  if (!args || Object.keys(args).length === 0) {
    return '—';
  }

  try {
    return JSON.stringify(args, null, 2);
  } catch {
    return String(args);
  }
}

function shortLabel(name: string): string {
  return name.replace(/_/g, ' ');
}
</script>

<template>
  <div class="flex flex-wrap gap-1.5 mb-2" aria-label="Tools usadas">
    <template v-for="(tool, index) in tools" :key="`${tool.name}-${index}`">
      <button
        type="button"
        class="bg-transparent border-none p-0 cursor-pointer"
        @click="togglePopover($event, index)"
      >
        <Tag severity="info" :value="shortLabel(tool.name)" class="hover:opacity-80 transition-opacity" />
      </button>

      <Popover :ref="(el) => setOpRef(el, index)">
        <div class="flex flex-col gap-2 min-w-48 max-w-xs text-left p-1">
          <p class="font-bold text-xs text-gray-900 m-0">{{ tool.name }}</p>
          <div>
            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider m-0 mb-1">Argumentos</p>
            <pre class="m-0 text-xs font-mono max-h-32 overflow-auto whitespace-pre-wrap bg-gray-50 p-2 rounded text-gray-800">{{ formatArgs(tool.arguments) }}</pre>
          </div>
          <div v-if="tool.result_summary">
            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider m-0 mb-1">Resultado</p>
            <pre class="m-0 text-xs font-mono max-h-40 overflow-auto whitespace-pre-wrap bg-gray-50 p-2 rounded text-gray-800">{{ tool.result_summary }}</pre>
          </div>
        </div>
      </Popover>
    </template>
  </div>
</template>
