<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import AppBrandTitle from '@/Components/ui/AppBrandTitle.vue';

interface Phase {
  id: number;
  name: string;
  total: number;
  done: number;
}

interface TaskItem {
  id: string;
  done: boolean;
  desc: string;
  phase: number;
  phaseName: string;
}

interface NamedCheck {
  name: string;
  path?: string;
  value?: string;
  ok: boolean;
}

interface UserStory {
  id: string;
  priority: string;
  title: string;
  phase: number;
  mvp: boolean;
}

const props = defineProps<{
  feature: string;
  generatedAt: string;
  progress: Record<string, number>;
  stack: Record<string, string>;
  tasks: {
    total: number;
    done: number;
    pending: number;
    phases: Phase[];
    items: TaskItem[];
  };
  artifacts: NamedCheck[];
  implementation: NamedCheck[];
  userStories: UserStory[];
  nextSteps: string[];
  runtime?: NamedCheck[];
}>();

const progressMetrics = computed(() =>
  Object.entries(props.progress).filter(([key]) => key !== 'overall'),
);

function pct(done: number, total: number): number {
  return total === 0 ? 0 : Math.round((done / total) * 100);
}

function formatMetricLabel(key: string): string {
  const labels: Record<string, string> = {
    planning: 'Planning',
    tasks: 'Tasks',
    implementation: 'Implementation',
  };

  return labels[key] ?? key;
}

function storyPhaseProgress(phaseId: number): { done: number; total: number; pct: number } {
  const phase = props.tasks.phases.find((p) => p.id === phaseId);
  if (!phase) {
    return { done: 0, total: 0, pct: 0 };
  }

  return { done: phase.done, total: phase.total, pct: pct(phase.done, phase.total) };
}

function storyTagVariant(story: UserStory): 'success' | 'warning' | 'neutral' {
  const { done, total } = storyPhaseProgress(story.phase);
  if (total > 0 && done === total) {
    return 'success';
  }
  if (done > 0) {
    return 'warning';
  }

  return 'neutral';
}
</script>

<template>
  <Head title="Spec Status — Internal" />

  <div class="spec-status">
    <header class="spec-status__header">
      <div>
        <p class="spec-status__eyebrow">Internal · no nav link</p>
        <AppBrandTitle tag="h1" class="spec-status__title" />
        <p class="spec-status__meta">
          Feature {{ feature }} · {{ generatedAt }}
        </p>
      </div>
      <div class="spec-status__header-actions">
        <div class="spec-status__overall" aria-label="Overall progress">
          <span class="spec-status__overall-value">{{ progress.overall }}%</span>
          <span class="spec-status__overall-label">overall</span>
        </div>
        <Link href="/">
          <Button label="Volver al chat" severity="secondary" size="small" />
        </Link>
      </div>
    </header>

    <section class="spec-status__metrics">
      <article v-for="[key, val] in progressMetrics" :key="key" class="metric-card">
        <h2 class="metric-card__label">{{ formatMetricLabel(key) }}</h2>
        <div class="metric-card__bar" role="progressbar" :aria-valuenow="val" aria-valuemin="0" aria-valuemax="100">
          <div class="metric-card__fill" :style="{ width: `${val}%` }" />
        </div>
        <p class="metric-card__value">{{ val }}%</p>
      </article>
    </section>

    <div class="spec-status__grid">
      <Card v-if="runtime?.length" class="spec-status__card spec-status__card--wide">
        <template #title><span class="text-sm font-bold text-gray-900">Runtime (live)</span></template>
        <template #content>
          <ul class="kv-list">
            <li v-for="r in runtime" :key="r.name" class="kv-list__row">
              <span class="kv-list__key">{{ r.name }}</span>
              <span class="kv-list__val" :class="{ 'kv-list__val--ok': r.ok }">
                {{ r.value ?? (r.ok ? 'OK' : '—') }}
              </span>
            </li>
          </ul>
        </template>
      </Card>

      <Card class="spec-status__card">
        <template #title><span class="text-sm font-bold text-gray-900">Stack</span></template>
        <template #content>
          <ul class="kv-list">
            <li v-for="(v, k) in stack" :key="k" class="kv-list__row">
              <span class="kv-list__key">{{ k }}</span>
              <span class="kv-list__val">{{ v }}</span>
            </li>
          </ul>
        </template>
      </Card>

      <Card class="spec-status__card">
        <template #title><span class="text-sm font-bold text-gray-900">User stories</span></template>
        <template #content>
          <ul class="story-list">
            <li v-for="s in userStories" :key="s.id" class="story-list__item">
              <Tag
                :severity="
                  storyPhaseProgress(s.phase).total > 0 &&
                  storyPhaseProgress(s.phase).done === storyPhaseProgress(s.phase).total
                    ? 'success'
                    : 'secondary'
                "
                :value="s.priority"
              />
              <Tag v-if="s.mvp" severity="info" value="MVP" />
              <span class="story-list__id">{{ s.id }}</span>
              <span class="story-list__title">{{ s.title }}</span>
              <span class="story-list__phase">
                Ph {{ s.phase }} · {{ storyPhaseProgress(s.phase).done }}/{{ storyPhaseProgress(s.phase).total }}
              </span>
            </li>
          </ul>
        </template>
      </Card>

      <Card class="spec-status__card">
        <template #title>
          <span class="text-sm font-bold text-gray-900">
            Artefactos ({{ artifacts.filter((a) => a.ok).length }}/{{ artifacts.length }})
          </span>
        </template>
        <template #content>
          <ul class="check-list">
            <li v-for="a in artifacts" :key="a.path" class="check-list__item" :class="{ 'check-list__item--ok': a.ok }">
              <span class="check-list__mark">{{ a.ok ? '✓' : '○' }}</span>
              {{ a.name }}
            </li>
          </ul>
        </template>
      </Card>

      <Card class="spec-status__card">
        <template #title><span class="text-sm font-bold text-gray-900">Implementación</span></template>
        <template #content>
          <ul class="check-list">
            <li v-for="i in implementation" :key="i.name" class="check-list__item" :class="{ 'check-list__item--ok': i.ok }">
              <span class="check-list__mark">{{ i.ok ? '✓' : '○' }}</span>
              {{ i.name }}
            </li>
          </ul>
        </template>
      </Card>
    </div>

    <Card class="spec-status__card spec-status__card--wide spec-status__card--spaced">
      <template #title>
        <span class="text-sm font-bold text-gray-900">
          Tasks {{ tasks.done }}/{{ tasks.total }} ({{ pct(tasks.done, tasks.total) }}%)
        </span>
      </template>
      <template #content>
        <div class="phase-bars">
          <div v-for="p in tasks.phases" :key="p.id" class="phase-bar">
            <div class="phase-bar__head">
              <span>Phase {{ p.id }}</span>
              <span>{{ p.done }}/{{ p.total }}</span>
            </div>
            <div class="phase-bar__track">
              <div class="phase-bar__fill" :style="{ width: `${pct(p.done, p.total)}%` }" />
            </div>
            <p class="phase-bar__name">{{ p.name }}</p>
          </div>
        </div>
        <ul class="task-list">
          <li
            v-for="t in tasks.items"
            :key="t.id"
            class="task-list__item"
            :class="{ 'task-list__item--done': t.done }"
          >
            <span class="task-list__id">{{ t.id }}</span>
            <span class="task-list__desc">{{ t.desc }}</span>
          </li>
        </ul>
      </template>
    </Card>

    <Card class="spec-status__card spec-status__card--wide">
      <template #title><span class="text-sm font-bold text-gray-900">Próximos pasos</span></template>
      <template #content>
        <ol class="next-list">
          <li v-for="(step, idx) in nextSteps" :key="idx">{{ step }}</li>
        </ol>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.spec-status {
  min-height: 100dvh;
  padding: var(--prinex-spacing-lg);
  background-color: var(--prinex-color-background);
  color: var(--prinex-color-text);
  font-family: var(--prinex-font-family-base);
}

.spec-status__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--prinex-spacing-md);
  margin-bottom: var(--prinex-spacing-lg);
  flex-wrap: wrap;
}

.spec-status__header-actions {
  display: flex;
  align-items: center;
  gap: var(--prinex-spacing-md);
}

.spec-status__eyebrow {
  font-size: var(--prinex-font-size-xs);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--prinex-color-text-muted);
  margin: 0 0 var(--prinex-spacing-xs);
}

.spec-status__title {
  font-size: var(--prinex-font-size-2xl);
  font-weight: var(--prinex-font-weight-semibold);
  margin: 0;
  color: var(--prinex-color-text);
}

.spec-status__meta {
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text-muted);
  margin: var(--prinex-spacing-xs) 0 0;
}

.spec-status__overall {
  text-align: center;
  padding: var(--prinex-spacing-sm) var(--prinex-spacing-md);
  border-radius: var(--prinex-radius-lg);
  background-color: var(--prinex-color-surface);
  border: 1px solid var(--prinex-color-border);
}

.spec-status__overall-value {
  display: block;
  font-size: var(--prinex-font-size-2xl);
  font-weight: var(--prinex-font-weight-bold);
  color: var(--prinex-color-primary);
}

.spec-status__overall-label {
  font-size: var(--prinex-font-size-xs);
  color: var(--prinex-color-text-muted);
}

.spec-status__metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: var(--prinex-spacing-md);
  margin-bottom: var(--prinex-spacing-lg);
}

.metric-card {
  padding: var(--prinex-spacing-md);
  border-radius: var(--prinex-radius-lg);
  background-color: var(--prinex-color-surface);
  border: 1px solid var(--prinex-color-border);
}

.metric-card__label {
  font-size: var(--prinex-font-size-sm);
  font-weight: var(--prinex-font-weight-medium);
  margin: 0 0 var(--prinex-spacing-sm);
  color: var(--prinex-color-text);
}

.metric-card__bar {
  height: 8px;
  border-radius: var(--prinex-radius-full);
  background-color: var(--prinex-color-border);
  overflow: hidden;
}

.metric-card__fill {
  height: 100%;
  background-color: var(--prinex-color-success);
  transition: width 0.3s ease;
}

.metric-card__value {
  margin: var(--prinex-spacing-xs) 0 0;
  font-size: var(--prinex-font-size-lg);
  font-weight: var(--prinex-font-weight-semibold);
  color: var(--prinex-color-text);
}

.spec-status__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--prinex-spacing-md);
  margin-bottom: var(--prinex-spacing-md);
}

.spec-status__card--wide {
  grid-column: 1 / -1;
}

.spec-status__card--spaced {
  margin-bottom: var(--prinex-spacing-md);
}

.kv-list,
.check-list,
.story-list,
.task-list,
.next-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.kv-list__row {
  display: flex;
  justify-content: space-between;
  gap: var(--prinex-spacing-sm);
  padding: var(--prinex-spacing-xs) 0;
  font-size: var(--prinex-font-size-sm);
  border-bottom: 1px solid var(--prinex-color-border);
}

.kv-list__row:last-child {
  border-bottom: none;
}

.kv-list__key {
  color: var(--prinex-color-text-muted);
  text-transform: capitalize;
}

.kv-list__val {
  color: var(--prinex-color-text);
  text-align: right;
  word-break: break-word;
}

.kv-list__val--ok {
  color: var(--prinex-color-success);
  font-weight: var(--prinex-font-weight-medium);
}

.story-list__item {
  display: grid;
  grid-template-columns: auto auto auto 1fr auto;
  gap: var(--prinex-spacing-sm);
  align-items: center;
  padding: var(--prinex-spacing-xs) 0;
  font-size: var(--prinex-font-size-sm);
  border-bottom: 1px solid var(--prinex-color-border);
}

.story-list__item:last-child {
  border-bottom: none;
}

.story-list__id {
  font-weight: var(--prinex-font-weight-semibold);
  color: var(--prinex-color-text);
}

.story-list__title {
  color: var(--prinex-color-text);
}

.story-list__phase {
  color: var(--prinex-color-text-muted);
  font-size: var(--prinex-font-size-xs);
}

.check-list__item {
  display: flex;
  gap: var(--prinex-spacing-sm);
  font-size: var(--prinex-font-size-sm);
  padding: var(--prinex-spacing-xs) 0;
  color: var(--prinex-color-text-muted);
}

.check-list__item--ok {
  color: var(--prinex-color-text);
}

.check-list__mark {
  flex-shrink: 0;
  width: 1rem;
  color: var(--prinex-color-success);
}

.check-list__item:not(.check-list__item--ok) .check-list__mark {
  color: var(--prinex-color-text-muted);
}

.phase-bars {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--prinex-spacing-md);
  margin-bottom: var(--prinex-spacing-md);
}

.phase-bar__head {
  display: flex;
  justify-content: space-between;
  font-size: var(--prinex-font-size-xs);
  color: var(--prinex-color-text);
  margin-bottom: var(--prinex-spacing-xs);
}

.phase-bar__track {
  height: 8px;
  border-radius: var(--prinex-radius-full);
  background-color: var(--prinex-color-border);
  overflow: hidden;
}

.phase-bar__fill {
  height: 100%;
  background-color: var(--prinex-color-success);
}

.phase-bar__name {
  font-size: var(--prinex-font-size-xs);
  color: var(--prinex-color-text-muted);
  margin: var(--prinex-spacing-xs) 0 0;
}

.task-list {
  max-height: 360px;
  overflow-y: auto;
  border-top: 1px solid var(--prinex-color-border);
  padding-top: var(--prinex-spacing-sm);
}

.task-list__item {
  display: flex;
  gap: var(--prinex-spacing-md);
  padding: var(--prinex-spacing-xs) 0;
  font-size: var(--prinex-font-size-sm);
  border-bottom: 1px solid var(--prinex-color-border);
  color: var(--prinex-color-text);
}

.task-list__item--done {
  color: var(--prinex-color-text-muted);
  text-decoration: line-through;
}

.task-list__id {
  flex-shrink: 0;
  font-weight: var(--prinex-font-weight-semibold);
  color: var(--prinex-color-primary);
  width: 2.75rem;
}

.task-list__desc {
  min-width: 0;
}

.next-list {
  padding-left: var(--prinex-spacing-lg);
  font-size: var(--prinex-font-size-sm);
  color: var(--prinex-color-text);
}

.next-list li {
  margin-bottom: var(--prinex-spacing-xs);
}
</style>
