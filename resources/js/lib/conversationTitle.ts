import type { ProjectSummary } from '@/types/chat';

const DEFAULT_TITLE = 'Nueva conversación';

export function isDefaultConversationTitle(title: string): boolean {
  return title.trim() === '' || title.trim() === DEFAULT_TITLE;
}

export function buildConversationTitle(
  message: string,
  projectName: string | null,
  projects: ProjectSummary[],
): string {
  const snippetMax = 36;
  const normalized = message.trim().replace(/\s+/g, ' ');

  let projectLabel = 'General';
  if (projectName) {
    const project = projects.find((p) => p.name === projectName);
    projectLabel = project?.display_name ?? projectName;
  }

  let snippet = normalized.slice(0, snippetMax);
  if (normalized.length > snippetMax) {
    snippet += '…';
  }

  if (snippet === '') {
    snippet = 'Consulta';
  }

  return `${projectLabel} · ${snippet}`.slice(0, 255);
}
