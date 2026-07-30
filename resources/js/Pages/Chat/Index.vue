<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';
import Message from 'primevue/message';
import Card from 'primevue/card';
import { useToast } from 'primevue/usetoast';

import CodebaseMenuDropdown from '@/Components/Chat/CodebaseMenuDropdown.vue';
import ChatMenuDropdown from '@/Components/Chat/ChatMenuDropdown.vue';
import AgentProfileSelector from '@/Components/Agent/AgentProfileSelector.vue';
import LlmModelsPanel from '@/Components/Llm/LlmModelsPanel.vue';
import ProjectList from '@/Components/Projects/ProjectList.vue';
import AddProjectModal from '@/Components/Projects/AddProjectModal.vue';
import ChatMessageList from '@/Components/Chat/ChatMessageList.vue';
import ChatInsightsPanel from '@/Components/Chat/ChatInsightsPanel.vue';
import ChatInput from '@/Components/Chat/ChatInput.vue';
import ConversationSidebar from '@/Components/Conversations/ConversationSidebar.vue';
import ConfirmModal from '@/Components/ui/ConfirmModal.vue';
import PromptModal from '@/Components/ui/PromptModal.vue';
import AppBrandTitle from '@/Components/ui/AppBrandTitle.vue';

import { useMcpStatus } from '@/composables/useMcpStatus';
import { useProjects } from '@/composables/useProjects';
import { useConversations } from '@/composables/useConversations';
import { useChatStream } from '@/composables/useChatStream';
import { useChatInsights } from '@/composables/useChatInsights';
import { useLlmModels } from '@/composables/useLlmModels';
import { useAgentProfiles } from '@/composables/useAgentProfiles';
import { buildConversationTitle, isDefaultConversationTitle } from '@/lib/conversationTitle';
import { countSummaryMessages, isSummaryStale } from '@/lib/conversationSummary';
import { readStoredBoolean, writeStoredBoolean } from '@/lib/storedBoolean';

import type { AgentProfileSummary } from '@/types/agent';
import type { ConversationSummary, McpStatus, ProjectSummary } from '@/types/chat';
import type { LlmModelProfile, LlmEnvSnapshot } from '@/types/llm';

const props = defineProps<{
  conversations: ConversationSummary[];
  projects: ProjectSummary[];
  activeConversationId: string | null;
  activeProjectName: string | null;
  mcpStatus: McpStatus;
  llmConfigured: boolean;
  llmModels: LlmModelProfile[];
  llmEnv: LlmEnvSnapshot;
  agentProfiles: AgentProfileSummary[];
}>();

const toast = useToast();
const mcp = useMcpStatus(props.mcpStatus);
const projectState = useProjects(props.projects);
const convState = useConversations(props.conversations);
const chat = useChatStream();
const insights = useChatInsights();
const llmModelState = useLlmModels(props.llmModels, props.llmEnv);
const agentProfileState = useAgentProfiles(props.agentProfiles);

const indexModalOpen = ref(false);
const llmModelsModalOpen = ref(false);
const profileModalOpen = ref(false);
const messageListRef = ref<InstanceType<typeof ChatMessageList> | null>(null);
const skipConversationReload = ref(false);

const SHOW_MESSAGE_DETAILS_KEY = 'chat.showMessageDetails';
const SHOW_PROJECTS_PANEL_KEY = 'chat.showProjectsPanel';
const SHOW_HISTORY_PANEL_KEY = 'chat.showHistoryPanel';

const showMessageDetails = ref(readStoredBoolean(SHOW_MESSAGE_DETAILS_KEY));
const showProjectsPanel = ref(readStoredBoolean(SHOW_PROJECTS_PANEL_KEY, true));
const showHistoryPanel = ref(readStoredBoolean(SHOW_HISTORY_PANEL_KEY, true));
const showInputDrawer = ref(false);

watch(showMessageDetails, (value) => writeStoredBoolean(SHOW_MESSAGE_DETAILS_KEY, value));
watch(showProjectsPanel, (value) => writeStoredBoolean(SHOW_PROJECTS_PANEL_KEY, value));
watch(showHistoryPanel, (value) => writeStoredBoolean(SHOW_HISTORY_PANEL_KEY, value));

const defaultLocalRepoPath = ref('/Users/alejandrorodriguez/APPs/codebase-llm-assistant');
const summaryGeneratingId = ref<string | null>(null);
const summaryModalOpen = ref(false);
const summaryModalConversationId = ref<string | null>(null);

const confirmState = ref({
  open: false,
  title: '',
  description: '',
  danger: false,
  confirmLabel: 'Confirmar',
  action: null as (() => void | Promise<void>) | null,
});

const renameState = ref({ open: false, id: '', title: '' });

if (props.activeConversationId) {
  convState.activeId.value = props.activeConversationId;
  const initialConversation = convState.items.value.find(
    (conversation) => conversation.id === props.activeConversationId,
  );
  if (initialConversation?.primary_project_name) {
    projectState.setActive(initialConversation.primary_project_name);
  }
}
if (props.activeProjectName) projectState.activeName.value = props.activeProjectName;

watch(convState.activeId, async (id) => {
  if (chat.streaming.value || skipConversationReload.value) return;

  if (id) {
    const conv = convState.items.value.find((c) => c.id === id);
    projectState.setActive(conv?.primary_project_name ?? null);
    await chat.loadMessages(id);
    convState.patchConversation(id, {
      messages_count: countSummaryMessages(chat.messages.value),
    });
    await messageListRef.value?.scrollToBottom('smooth');
    return;
  }

  chat.messages.value = [];
  await insights.refresh(projectState.activeName.value);
});

watch(
  () => projectState.activeName.value,
  (projectName) => {
    if (!convState.activeId.value) {
      void insights.refresh(projectName);
    }
  },
);

void insights.refresh(projectState.activeName.value);

const canChat = computed(
  () => props.llmConfigured && mcp.status.value.status === 'running',
);

const showChatInput = computed(
  () => !!convState.activeId.value || showInputDrawer.value,
);

const isInsightsView = computed(() => !convState.activeId.value);

const activeProject = computed(() => {
  if (!projectState.activeName.value) return null;
  return projectState.projects.value.find((p) => p.name === projectState.activeName.value) ?? null;
});

const activeConversation = computed(() => {
  if (!convState.activeId.value) return null;
  return convState.items.value.find((conversation) => conversation.id === convState.activeId.value) ?? null;
});

const activeSummaryIsStale = computed(() => {
  if (!activeConversation.value) return false;

  const liveCount = convState.activeId.value
    ? countSummaryMessages(chat.messages.value)
    : activeConversation.value.messages_count;

  return isSummaryStale({
    ...activeConversation.value,
    messages_count: liveCount,
  });
});

const summaryModalConversation = computed(() => {
  if (!summaryModalConversationId.value) return null;
  return convState.items.value.find((conversation) => conversation.id === summaryModalConversationId.value) ?? null;
});

const summaryModalText = computed(() => summaryModalConversation.value?.summary ?? '');
const summaryModalIsStale = computed(() => isSummaryStale(summaryModalConversation.value));

async function onCreateConversation() {
  await convState.create(undefined, projectState.activeName.value);
}

async function onSend(text: string) {
  const title = buildConversationTitle(text, projectState.activeName.value, projectState.projects.value);
  let created = false;

  if (!convState.activeId.value) {
    skipConversationReload.value = true;
    try {
      await convState.create(title, projectState.activeName.value);
      created = true;
    } finally {
      skipConversationReload.value = false;
    }
  }

  if (!convState.activeId.value) return;

  if (!created) {
    const conv = convState.items.value.find((c) => c.id === convState.activeId.value);
    if (conv && isDefaultConversationTitle(conv.title)) {
      void convState.rename(conv.id, title);
    }
  }

  await chat.send(
    convState.activeId.value,
    text,
    projectState.activeName.value,
    agentProfileState.activeSlug.value,
  );

  convState.patchConversation(convState.activeId.value, {
    messages_count: countSummaryMessages(chat.messages.value),
  });
}

function showToast(
  message: string,
  variant: 'info' | 'success' | 'warning' | 'danger' = 'info',
) {
  const severityMap: Record<string, 'info' | 'success' | 'warn' | 'error'> = {
    info: 'info',
    success: 'success',
    warning: 'warn',
    danger: 'error',
  };
  toast.add({
    severity: severityMap[variant] ?? 'info',
    summary: message,
    life: 3000,
  });
}

async function onMcpStart() {
  await mcp.start();
  showToast(mcp.status.value.message ?? 'Estado MCP actualizado', 'info');
  await projectState.refresh();
}

async function onMcpStop() {
  confirmState.value = {
    open: true,
    title: 'Parar MCP',
    description: '¿Parar MCP en el host? Tendrás que levantarlo manualmente después.',
    danger: true,
    confirmLabel: 'Parar',
    action: async () => {
      await mcp.stop();
      showToast('MCP detenido (manual en host)', 'warning');
    },
  };
}

async function onIndexLocalProject(repoPath: string) {
  const ok = await projectState.indexRepo(repoPath);
  indexModalOpen.value = false;
  showToast(
    ok ? 'Proyecto indexado' : 'Error al indexar',
    ok ? 'success' : 'danger',
  );
}

async function onCloneBitbucketProject(payload: {
  repository_url: string;
  username: string;
  api_token: string;
}) {
  const result = await projectState.cloneFromBitbucket(payload);
  if (result.ok) {
    indexModalOpen.value = false;
    showToast('Repositorio clonado e indexado', 'success');
    return;
  }

  showToast(
    result.message ?? 'No se pudo clonar el repositorio',
    'danger',
  );
}

async function onRemoveProject(name: string) {
  confirmState.value = {
    open: true,
    title: 'Eliminar índice',
    description: `¿Eliminar el índice del proyecto "${name}"?`,
    danger: true,
    confirmLabel: 'Eliminar',
    action: async () => {
      await projectState.remove(name);
    },
  };
}

function onRenameConversation(id: string) {
  const conv = convState.items.value.find((c) => c.id === id);
  renameState.value = {
    open: true,
    id,
    title: conv?.title ?? '',
  };
}

async function submitRename() {
  const title = renameState.value.title.trim();
  if (!title) return;

  await convState.rename(renameState.value.id, title);
  renameState.value.open = false;
}

async function onRemoveConversation(id: string) {
  confirmState.value = {
    open: true,
    title: 'Eliminar conversación',
    description: 'Esta acción no se puede deshacer.',
    danger: true,
    confirmLabel: 'Eliminar',
    action: async () => {
      await convState.remove(id);
    },
  };
}

async function onConfirmDialog() {
  const action = confirmState.value.action;
  confirmState.value.open = false;
  if (action) await action();
}

async function onSaveLlmModels() {
  try {
    await llmModelState.save(llmModelState.models.value);
    llmModelsModalOpen.value = false;
    showToast('Modelos guardados. Fallback activo en orden.', 'success');
  } catch {
    showToast(llmModelState.error.value ?? 'Error al guardar modelos', 'danger');
  }
}

function toggleProjectsPanel() {
  showProjectsPanel.value = !showProjectsPanel.value;
}

function toggleHistoryPanel() {
  showHistoryPanel.value = !showHistoryPanel.value;
}

function toggleMessageDetails() {
  showMessageDetails.value = !showMessageDetails.value;
}

function onSelectProject(name: string) {
  if (isInsightsView.value && projectState.activeName.value === name && !showInputDrawer.value) {
    showInputDrawer.value = true;
    return;
  }

  projectState.select(name);

  if (isInsightsView.value) {
    showInputDrawer.value = !!projectState.activeName.value;
  }
}

function onDeselectConversation() {
  convState.deselect();
  showInputDrawer.value = false;
}

function onSelectConversation(id: string) {
  convState.select(id);
}

async function onGenerateSummary(conversationId?: string) {
  const id = conversationId ?? convState.activeId.value;
  if (!id) return;

  summaryGeneratingId.value = id;

  try {
    const updated = await convState.generateSummary(id);
    if (!updated) {
      showToast('No se pudo generar el resumen', 'danger');
      return;
    }

    showToast('Resumen guardado', 'success');

    if (summaryModalOpen.value && summaryModalConversationId.value === id) {
      summaryModalConversationId.value = id;
    }
  } finally {
    summaryGeneratingId.value = null;
  }
}

function onViewSummary(conversationId: string) {
  summaryModalConversationId.value = conversationId;
  summaryModalOpen.value = true;
}

function onViewActiveSummary() {
  if (!convState.activeId.value) return;
  onViewSummary(convState.activeId.value);
}

const activeProfileLabel = computed(
  () => agentProfileState.activeProfile.value?.label ?? 'Perfil',
);

function openSpecDashboard() {
  window.open('/internal/spec-status', '_blank', 'noopener,noreferrer');
}

function onOpenMcpGraph() {
  mcp.openGraph(projectState.activeName.value);
}
</script>

<template>
  <Head title="Chat" />

  <div class="chat-app">
    <a class="chat-app__skip" href="#chat-main">Ir al chat</a>

    <header class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2">
        <AppBrandTitle class="text-base font-bold text-gray-900" />
      </div>
      <div class="flex items-center gap-2">
        <CodebaseMenuDropdown
          :status="mcp.status.value"
          :loading="mcp.loading.value"
          @start="onMcpStart"
          @stop="onMcpStop"
          @open-graph="onOpenMcpGraph"
        />
        <ChatMenuDropdown
          :active-profile-label="activeProfileLabel"
          :show-message-details="showMessageDetails"
          @open-models="llmModelsModalOpen = true"
          @open-profile="profileModalOpen = true"
          @toggle-metadata="toggleMessageDetails"
        />
        <AgentProfileSelector
          v-model:open="profileModalOpen"
          :profiles="agentProfileState.profiles"
          :active-slug="agentProfileState.activeSlug.value"
          @select="agentProfileState.select"
        />
        <Button label="Spec" icon="pi pi-th-large" severity="secondary" size="small" @click="openSpecDashboard" />
      </div>
    </header>

    <div class="chat-app__layout">
      <div
        class="chat-app__panel-curtain chat-app__panel-curtain--projects"
        :class="{ 'chat-app__panel-curtain--collapsed': !showProjectsPanel }"
        :aria-hidden="!showProjectsPanel"
        :inert="!showProjectsPanel ? true : undefined"
      >
        <Card class="chat-app__panel chat-app__panel--projects shadow-none border border-gray-200">
          <template #title>
            <div class="flex items-center justify-between">
              <span class="text-sm font-bold text-gray-900">Proyectos</span>
              <Button
                icon="pi pi-plus"
                severity="primary"
                size="small"
                aria-label="Nuevo proyecto"
                @click="indexModalOpen = true"
              />
            </div>
          </template>
          <template #content>
            <div class="chat-app__panel-body">
              <ProjectList
                v-model:search="projectState.search.value"
                :projects="projectState.filtered.value"
                :active-name="projectState.activeName.value"
                :loading="projectState.loading.value"
                @select="onSelectProject"
                @remove="onRemoveProject"
              />
            </div>
          </template>
        </Card>
      </div>

      <main id="chat-main" class="chat-app__panel chat-app__panel--chat">
        <div class="chat-app__float-slot chat-app__float-slot--left">
          <Button
            :icon="showProjectsPanel ? 'pi pi-angle-double-left' : 'pi pi-angle-double-right'"
            severity="secondary"
            size="small"
            :aria-label="showProjectsPanel ? 'Ocultar proyectos' : 'Proyectos'"
            @click="toggleProjectsPanel"
          />
        </div>

        <div class="chat-app__float-slot chat-app__float-slot--right">
          <Button
            :icon="showHistoryPanel ? 'pi pi-angle-double-right' : 'pi pi-angle-double-left'"
            severity="secondary"
            size="small"
            :aria-label="showHistoryPanel ? 'Ocultar historial' : 'Historial'"
            @click="toggleHistoryPanel"
          />
        </div>

        <div class="chat-app__chat-body">
          <div class="chat-app__chat-main">
            <Message v-if="!llmConfigured" severity="warn" :closable="false" class="mb-3">
              Añade LLM_API_KEY en .env y reinicia la app.
            </Message>
            <Message
              v-else-if="mcp.status.value.status !== 'running'"
              severity="warn"
              :closable="false"
              class="mb-3"
            >
              {{ mcp.status.value.message ?? 'MCP no disponible' }}
            </Message>
            <Message v-if="chat.error.value" severity="error" :closable="false" class="mb-3">
              <pre class="chat-app__error">{{ chat.error.value }}</pre>
            </Message>

            <Message
              v-if="convState.activeId.value && activeSummaryIsStale"
              severity="warn"
              :closable="false"
              class="mb-3"
            >
              La conversación ha avanzado desde el último resumen. Genera uno nuevo.
            </Message>

            <ChatInsightsPanel
              v-if="!convState.activeId.value"
              :insights="insights.data.value"
              :loading="insights.loading.value"
              :error="insights.error.value"
              :project-label="activeProject?.display_name ?? activeProject?.name ?? null"
              @new-conversation="onCreateConversation"
            />
            <ChatMessageList
              v-else
              ref="messageListRef"
              :messages="chat.messages.value"
              :streaming="chat.streaming.value"
              :show-details="showMessageDetails"
            />
          </div>

          <div
            class="chat-app__input-curtain"
            :class="{ 'chat-app__input-curtain--collapsed': !showChatInput }"
            :aria-hidden="!showChatInput"
            :inert="!showChatInput ? true : undefined"
          >
            <div class="chat-app__input-drawer">
              <ChatInput
                :disabled="!canChat || chat.streaming.value"
                :project-label="activeProject?.display_name ?? activeProject?.name ?? null"
                :project-stack="activeProject?.primary_stack ?? null"
                @send="onSend"
              >
                <template v-if="convState.activeId.value" #actions-start>
                  <Button
                    label="Generar resumen"
                    severity="secondary"
                    size="small"
                    :loading="summaryGeneratingId === convState.activeId.value"
                    @click="onGenerateSummary()"
                  />
                  <Button
                    label="Resumen"
                    severity="secondary"
                    size="small"
                    :disabled="!activeConversation?.summary"
                    @click="onViewActiveSummary"
                  />
                </template>
              </ChatInput>
            </div>
          </div>
        </div>
      </main>

      <div
        class="chat-app__panel-curtain chat-app__panel-curtain--history"
        :class="{ 'chat-app__panel-curtain--collapsed': !showHistoryPanel }"
        :aria-hidden="!showHistoryPanel"
        :inert="!showHistoryPanel ? true : undefined"
      >
        <Card class="chat-app__panel chat-app__panel--history shadow-none border border-gray-200">
          <template #title>
            <div class="flex items-center justify-between">
              <span class="text-sm font-bold text-gray-900">Historial</span>
              <div class="flex items-center gap-1">
                <Button
                  v-if="convState.activeId.value"
                  label="Cerrar"
                  severity="secondary"
                  size="small"
                  @click="onDeselectConversation"
                />
                <Button
                  icon="pi pi-plus"
                  severity="primary"
                  size="small"
                  aria-label="Nueva"
                  @click="onCreateConversation"
                />
              </div>
            </div>
          </template>
          <template #content>
            <div class="chat-app__panel-body">
              <ConversationSidebar
                v-model:search="convState.search.value"
                :conversations="convState.filtered()"
                :active-id="convState.activeId.value"
                :summarizing-id="summaryGeneratingId"
                @select="onSelectConversation"
                @rename="onRenameConversation"
                @remove="onRemoveConversation"
                @summarize="onGenerateSummary"
                @view-summary="onViewSummary"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <Dialog
      v-model:visible="llmModelsModalOpen"
      header="Modelos LLM (fallback)"
      modal
      :style="{ width: '600px' }"
    >
      <p class="text-xs text-gray-500 mb-3">Configura la cadena de modelos y su orden de prioridad.</p>
      <LlmModelsPanel
        :models="llmModelState.models.value"
        :saving="llmModelState.saving.value"
        :env="llmEnv"
        @update:models="llmModelState.models.value = $event"
        @add="llmModelState.addModel()"
        @remove="llmModelState.removeModel($event)"
        @reorder="(from: number, to: number) => llmModelState.reorder(from, to)"
        @save="onSaveLlmModels"
      />
    </Dialog>

    <AddProjectModal
      v-model:open="indexModalOpen"
      :default-local-path="defaultLocalRepoPath"
      :loading="projectState.loading.value"
      @index-local="onIndexLocalProject"
      @clone-bitbucket="onCloneBitbucketProject"
    />

    <ConfirmModal
      v-model:open="confirmState.open"
      :title="confirmState.title"
      :description="confirmState.description"
      :danger="confirmState.danger"
      :confirm-label="confirmState.confirmLabel"
      @confirm="onConfirmDialog"
    />

    <PromptModal
      v-model:open="renameState.open"
      v-model="renameState.title"
      title="Renombrar conversación"
      description="Introduce un título para esta conversación."
      label="Título"
      confirm-label="Renombrar"
      @confirm="submitRename"
    />

    <Dialog
      v-model:visible="summaryModalOpen"
      header="Resumen de conversación"
      modal
      :style="{ width: '480px' }"
    >
      <p class="text-xs text-gray-500 mb-3">Resumen guardado de la conversación seleccionada.</p>
      <Message v-if="summaryModalIsStale" severity="warn" :closable="false" class="mb-3">
        La conversación ha avanzado desde este resumen.
      </Message>
      <p v-if="summaryModalText" class="text-sm text-gray-800 leading-relaxed">{{ summaryModalText }}</p>
      <div v-else class="text-center py-6 text-gray-500 text-xs">
        Sin resumen. Genera un resumen para verlo aquí.
      </div>
      <template #footer>
        <div class="flex justify-end gap-2 mt-4">
          <Button label="Cerrar" severity="secondary" text @click="summaryModalOpen = false" />
          <Button
            v-if="summaryModalConversationId"
            label="Regenerar"
            :loading="summaryGeneratingId === summaryModalConversationId"
            @click="onGenerateSummary(summaryModalConversationId)"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.chat-app {
  height: 100dvh;
  max-height: 100dvh;
  width: 100%;
  max-width: 100vw;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: var(--prinex-color-background);
  color: var(--prinex-color-text);
  font-family: var(--prinex-font-family-base);
}

.chat-app__brand-title {
  font-size: var(--prinex-font-size-md);
  font-weight: var(--prinex-font-weight-semibold);
}

.chat-app__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--prinex-spacing-sm);
}

.chat-app__skip {
  position: absolute;
  left: -9999px;
  top: auto;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.chat-app__skip:focus {
  position: fixed;
  left: var(--prinex-spacing-md);
  top: var(--prinex-spacing-md);
  width: auto;
  height: auto;
  padding: var(--prinex-spacing-sm) var(--prinex-spacing-md);
  background: var(--prinex-color-surface);
  border: 1px solid var(--prinex-color-border);
  border-radius: var(--prinex-radius-md);
  z-index: var(--prinex-z-tooltip, 1000);
}

.chat-app__layout {
  --chat-projects-width: 280px;
  --chat-history-width: 260px;
  --chat-panel-curtain-duration: 0.3s;
  --chat-panel-curtain-ease: cubic-bezier(0.4, 0, 0.2, 1);
  flex: 1;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 0;
  min-height: 0;
  overflow: hidden;
  width: 100%;
}

.chat-app__panel-curtain {
  min-width: 0;
  min-height: 0;
  overflow: hidden;
  transition: width var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease);
  will-change: width;
}

.chat-app__panel-curtain--projects {
  width: var(--chat-projects-width);
}

.chat-app__panel-curtain--history {
  width: var(--chat-history-width);
}

.chat-app__panel-curtain--projects.chat-app__panel-curtain--collapsed {
  width: 0;
}

.chat-app__panel-curtain--history.chat-app__panel-curtain--collapsed {
  width: 0;
}

.chat-app__panel-curtain--projects > .chat-app__panel {
  width: var(--chat-projects-width);
  min-width: var(--chat-projects-width);
  transition: transform var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease);
  transform: translateX(0);
}

.chat-app__panel-curtain--history > .chat-app__panel {
  width: var(--chat-history-width);
  min-width: var(--chat-history-width);
  transition: transform var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease);
  transform: translateX(0);
}

.chat-app__panel-curtain--projects.chat-app__panel-curtain--collapsed > .chat-app__panel {
  transform: translateX(-100%);
}

.chat-app__panel-curtain--history.chat-app__panel-curtain--collapsed > .chat-app__panel {
  transform: translateX(100%);
}

.chat-app__panel-curtain--collapsed {
  pointer-events: none;
}

.chat-app__panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
  min-width: 0;
  height: 100%;
  overflow: hidden;
  border-radius: 0;
  border-left: none;
  border-right: 1px solid var(--prinex-color-border);
  box-shadow: none;
}

.chat-app__float-slot {
  position: absolute;
  top: 50%;
  z-index: var(--prinex-z-sticky, 2);
  transform: translateY(-50%);
}

.chat-app__float-slot--left {
  left: 0;
  transform: translate(-50%, -50%);
}

.chat-app__float-slot--right {
  right: 0;
  transform: translate(50%, -50%);
}

.chat-app__panel--projects,
.chat-app__panel--history {
  overflow: hidden;
}

.chat-app__panel-body {
  flex: 1;
  min-height: 0;
  min-width: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-app__panel--chat {
  position: relative;
  z-index: 1;
  background: var(--prinex-color-surface-muted);
  padding: var(--prinex-spacing-md);
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
  overflow: visible;
}

.chat-app__chat-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-app__chat-main {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: var(--prinex-spacing-md);
  overflow: hidden;
}

.chat-app__input-curtain {
  flex-shrink: 0;
  display: grid;
  grid-template-rows: 1fr;
  min-height: 0;
  margin-top: var(--prinex-spacing-md);
  transition:
    grid-template-rows var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease),
    margin-top var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease);
}

.chat-app__input-curtain--collapsed {
  grid-template-rows: 0fr;
  margin-top: 0;
  pointer-events: none;
}

.chat-app__input-drawer {
  display: flex;
  flex-direction: column;
  gap: var(--prinex-spacing-sm);
  min-height: 0;
  overflow: hidden;
  transition: transform var(--chat-panel-curtain-duration) var(--chat-panel-curtain-ease);
  transform: translateY(0);
}

.chat-app__input-curtain--collapsed .chat-app__input-drawer {
  transform: translateY(100%);
}

.chat-app__summary-text {
  margin: 0;
  white-space: pre-wrap;
  line-height: var(--prinex-font-line-height-normal, 1.5);
  font-size: var(--prinex-font-size-sm);
}

.chat-app__error {
  margin: 0;
  white-space: pre-wrap;
  font-family: var(--prinex-font-family-mono, ui-monospace, monospace);
  font-size: var(--prinex-font-size-xs);
  line-height: var(--prinex-font-line-height-normal, 1.45);
}

@media (max-width: 1024px) {
  .chat-app__layout {
    --chat-projects-width: 240px;
    --chat-history-width: 240px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .chat-app__layout {
    --chat-panel-curtain-duration: 0.01ms;
  }

  .chat-app__panel-curtain,
  .chat-app__panel-curtain > .chat-app__panel,
  .chat-app__input-curtain,
  .chat-app__input-drawer {
    transition: none;
  }
}
</style>
