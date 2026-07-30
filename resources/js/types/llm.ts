export interface LlmEnvSnapshot {
  model: string;
  base_url: string;
  api_key_preview: string;
  driver: string;
}

export interface LlmModelProfile {
  id: string;
  model: string;
  label: string | null;
  sort_order: number;
  enabled: boolean;
  use_env_credentials: boolean;
  base_url: string | null;
  api_key_preview: string | null;
  api_key_set: boolean;
  /** Solo en cliente al editar; no persiste hasta guardar */
  api_key?: string;
}

export interface LlmModelsPayload {
  models: LlmModelProfile[];
  env?: LlmEnvSnapshot;
}
