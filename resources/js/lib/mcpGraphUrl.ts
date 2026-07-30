export function buildMcpGraphUrl(uiUrl: string, projectName?: string | null): string {
  if (!projectName?.trim()) {
    return uiUrl;
  }

  const url = new URL(uiUrl);
  url.searchParams.set('project', projectName);

  return url.toString();
}
