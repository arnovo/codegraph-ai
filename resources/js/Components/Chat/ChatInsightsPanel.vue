<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import type { ChatInsightsData } from '@/types/chat';

const props = defineProps<{
  insights: ChatInsightsData | null;
  loading?: boolean;
  error?: string | null;
  projectLabel?: string | null;
}>();

const emit = defineEmits<{
  newConversation: [];
}>();

const hasData = computed(() => {
  if (!props.insights) return false;

  return props.insights.activity.total_user_questions > 0;
});

const maxDailyCount = computed(() => {
  if (!props.insights?.activity.messages_by_day.length) return 1;

  return Math.max(...props.insights.activity.messages_by_day.map((item) => item.count), 1);
});
</script>

<template>
  <div class="flex-1 min-h-0 overflow-y-auto p-4">
    <header class="flex items-start justify-between gap-4 mb-4">
      <div>
        <h2 class="text-lg font-bold text-gray-900 m-0">Resumen de uso</h2>
        <p class="text-xs text-gray-500 m-0 mt-0.5">
          {{
            projectLabel
              ? `Métricas del proyecto ${projectLabel} y actividad global.`
              : 'Métricas globales del asistente.'
          }}
        </p>
      </div>
      <Button label="Nueva conversación" icon="pi pi-plus" size="small" @click="emit('newConversation')" />
    </header>

    <div v-if="loading" class="flex items-center justify-center gap-2 py-12 text-gray-500 text-sm">
      <i class="pi pi-spin pi-spinner text-lg" />
      <span>Cargando resumen…</span>
    </div>

    <div
      v-else-if="error"
      class="flex flex-col items-center justify-center p-8 text-center text-red-600 bg-red-50 rounded-lg border border-red-200"
    >
      <i class="pi pi-exclamation-triangle text-3xl mb-2" />
      <p class="font-semibold text-sm m-0">Error al cargar</p>
      <p class="text-xs text-red-500 m-0 mt-1">{{ error }}</p>
    </div>

    <div
      v-else-if="!hasData"
      class="flex flex-col items-center justify-center p-12 text-center text-gray-500 bg-gray-50 rounded-lg border border-gray-200"
    >
      <i class="pi pi-chart-bar text-4xl mb-3 text-gray-400" />
      <p class="font-bold text-base text-gray-800 m-0">Sin actividad todavía</p>
      <p class="text-xs text-gray-500 max-w-sm my-2">
        Selecciona un proyecto y empieza una conversación para ver métricas aquí.
      </p>
      <Button label="Nueva conversación" icon="pi pi-plus" size="small" class="mt-2" @click="emit('newConversation')" />
    </div>

    <div v-else-if="insights" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Actividad</span></template>
        <template #content>
          <dl class="grid grid-cols-2 gap-3 text-xs m-0">
            <div>
              <dt class="text-gray-500">Total preguntas</dt>
              <dd class="text-base font-bold text-gray-900 m-0">{{ insights.activity.total_user_questions }}</dd>
            </div>
            <div v-if="insights.activity.project_user_questions > 0">
              <dt class="text-gray-500">En proyecto activo</dt>
              <dd class="text-base font-bold text-gray-900 m-0">{{ insights.activity.project_user_questions }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Últimos 7 días</dt>
              <dd class="text-base font-bold text-gray-900 m-0">{{ insights.activity.questions_last_7_days }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Últimos 30 días</dt>
              <dd class="text-base font-bold text-gray-900 m-0">{{ insights.activity.questions_last_30_days }}</dd>
            </div>
          </dl>
          <p
            v-if="insights.projects.active_project_share_percent !== null"
            class="text-xs text-gray-500 mt-3 pt-2 border-t border-gray-100 m-0"
          >
            El proyecto activo concentra el {{ insights.projects.active_project_share_percent }}% de las preguntas.
          </p>
        </template>
      </Card>

      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Actividad diaria (14 días)</span></template>
        <template #content>
          <ul v-if="insights.activity.messages_by_day.length" class="flex flex-col gap-1.5 p-0 m-0 list-none text-xs">
            <li
              v-for="day in insights.activity.messages_by_day"
              :key="day.date"
              class="flex items-center gap-2"
            >
              <span class="w-10 text-gray-500 text-right">{{ day.date.slice(5) }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div
                  class="bg-blue-600 h-full rounded-full transition-all"
                  :style="{ width: `${(day.count / maxDailyCount) * 100}%` }"
                />
              </div>
              <span class="w-6 text-gray-700 font-medium text-right">{{ day.count }}</span>
            </li>
          </ul>
          <p v-else class="text-xs text-gray-400 m-0">Sin mensajes en el periodo.</p>
        </template>
      </Card>

      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Proyectos más consultados</span></template>
        <template #content>
          <ul v-if="insights.projects.top_by_questions.length" class="flex flex-col gap-2 p-0 m-0 list-none text-xs">
            <li v-for="project in insights.projects.top_by_questions" :key="project.name" class="flex justify-between items-center">
              <span class="truncate text-gray-800">{{ project.display_name }}</span>
              <Tag severity="secondary" :value="project.question_count" />
            </li>
          </ul>
          <p v-else class="text-xs text-gray-400 m-0">Sin datos de proyectos.</p>
        </template>
      </Card>

      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Preguntas frecuentes</span></template>
        <template #content>
          <ul v-if="insights.frequent_questions.length" class="flex flex-col gap-2 p-0 m-0 list-none text-xs">
            <li v-for="item in insights.frequent_questions" :key="item.text" class="flex justify-between items-center gap-2">
              <span class="truncate text-gray-800">{{ item.text }}</span>
              <Tag severity="info" :value="item.count" />
            </li>
          </ul>
          <p v-else class="text-xs text-gray-400 m-0">Sin preguntas repetidas.</p>
        </template>
      </Card>

      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Tools MCP</span></template>
        <template #content>
          <ul v-if="insights.tools.by_name.length" class="flex flex-col gap-2 p-0 m-0 list-none text-xs">
            <li v-for="tool in insights.tools.by_name" :key="tool.name" class="flex justify-between items-center">
              <span class="text-gray-800">{{ tool.name }}</span>
              <Tag severity="secondary" :value="tool.count" />
            </li>
          </ul>
          <p v-else class="text-xs text-gray-400 m-0">Sin uso de tools registrado.</p>
        </template>
      </Card>

      <Card class="shadow-none border border-gray-200">
        <template #title><span class="text-sm font-bold text-gray-900">Modelos LLM</span></template>
        <template #content>
          <ul v-if="insights.models.top_by_usage.length" class="flex flex-col gap-2 p-0 m-0 list-none text-xs">
            <li v-for="model in insights.models.top_by_usage" :key="model.model" class="flex justify-between items-center">
              <span class="truncate text-gray-800">{{ model.model }}</span>
              <Tag severity="secondary" :value="model.count" />
            </li>
          </ul>
          <p v-else class="text-xs text-gray-400 m-0">Sin respuestas registradas.</p>
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.chat-insights {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: var(--prinex-spacing-sm);
}

.chat-insights__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--prinex-spacing-md);
  margin-bottom: var(--prinex-spacing-md);
}

.chat-insights__title {
  margin: 0;
  font-size: var(--prinex-font-size-md);
  font-weight: var(--prinex-font-weight-semibold);
}

.chat-insights__subtitle {
  margin: var(--prinex-spacing-xs) 0 0;
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 72%, transparent));
}

.chat-insights__loading {
  display: inline-flex;
  align-items: center;
  gap: var(--prinex-spacing-sm);
  font-size: var(--prinex-font-size-sm);
}

.chat-insights__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--prinex-spacing-md);
}

.chat-insights__card {
  min-width: 0;
}

.chat-insights__metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--prinex-spacing-sm);
  margin: 0;
}

.chat-insights__metrics dt {
  font-size: var(--prinex-font-size-xs);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 72%, transparent));
}

.chat-insights__metrics dd {
  margin: 0.15rem 0 0;
  font-size: var(--prinex-font-size-lg);
  font-weight: var(--prinex-font-weight-semibold);
}

.chat-insights__note,
.chat-insights__empty-copy {
  margin: var(--prinex-spacing-sm) 0 0;
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 72%, transparent));
}

.chat-insights__list,
.chat-insights__sublist,
.chat-insights__bars {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--prinex-spacing-xs);
}

.chat-insights__sublist {
  margin-top: var(--prinex-spacing-sm);
  padding-top: var(--prinex-spacing-sm);
  border-top: 1px solid var(--prinex-color-border);
}

.chat-insights__list li,
.chat-insights__sublist li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--prinex-spacing-sm);
}

.chat-insights__question {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: var(--prinex-font-size-sm);
}

.chat-insights__bar-row {
  display: grid;
  grid-template-columns: 3rem 1fr 1.5rem;
  align-items: center;
  gap: var(--prinex-spacing-xs);
}

.chat-insights__bar-label,
.chat-insights__bar-value {
  font-size: var(--prinex-font-size-xs);
  color: var(--prinex-color-text-muted, color-mix(in srgb, var(--prinex-color-text) 72%, transparent));
}

.chat-insights__bar-track {
  height: 0.5rem;
  border-radius: var(--prinex-radius-full, 999px);
  background: var(--prinex-color-surface-muted);
  overflow: hidden;
}

.chat-insights__bar-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--prinex-color-primary);
}

@media (max-width: 1024px) {
  .chat-insights__grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
