<script setup lang="ts">
const props = defineProps<{
  file: string;
  line?: number;
  symbol?: string;
}>();

function copyPath() {
  const ref = props.line ? `${props.file}:${props.line}` : props.file;
  navigator.clipboard.writeText(ref);
}
</script>

<template>
  <a
    href="#"
    class="inline-flex items-center gap-1 font-mono text-xs text-blue-600 hover:text-blue-800 underline decoration-dotted"
    title="Copiar ruta"
    @click.prevent="copyPath"
  >
    <span>{{ file }}<template v-if="line">:{{ line }}</template></span>
    <span v-if="symbol" class="text-gray-500">({{ symbol }})</span>
  </a>
</template>
