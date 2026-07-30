export function readStoredString(key: string, defaultValue: string): string {
  try {
    const raw = localStorage.getItem(key);
    if (raw === null || raw.trim() === '') {
      return defaultValue;
    }

    return raw;
  } catch {
    return defaultValue;
  }
}

export function writeStoredString(key: string, value: string): void {
  try {
    localStorage.setItem(key, value);
  } catch {
    // ignore quota / private mode
  }
}
