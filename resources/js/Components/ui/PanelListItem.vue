<script setup lang="ts">
defineProps<{
  active?: boolean;
}>();

const emit = defineEmits<{
  select: [];
}>();
</script>

<template>
  <li
    class="panel-item"
    :class="{ 'panel-item--active': active }"
    :aria-current="active ? 'true' : undefined"
    @click="emit('select')"
  >
    <span v-if="active" class="panel-item__indicator" aria-hidden="true" />
    <div class="panel-item__body">
      <slot />
    </div>
    <div v-if="$slots.actions" class="panel-item__actions" @click.stop>
      <slot name="actions" />
    </div>
  </li>
</template>

<style scoped>
.panel-item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.5rem 0.5rem 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #ffffff;
  cursor: pointer;
  min-width: 0;
  transition: all 0.15s ease-in-out;
}

.panel-item:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.panel-item--active {
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.panel-item__indicator {
  position: absolute;
  left: 0.35rem;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  border-radius: 999px;
  background: #2563eb;
}

.panel-item__body {
  min-width: 0;
  flex: 1;
}

.panel-item__actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-shrink: 0;
}
</style>
