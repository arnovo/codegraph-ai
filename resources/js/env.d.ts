/// <reference types="vite/client" />

declare module '@/revision.json' {
  const value: { number: number; builtAt: string | null };

  export default value;
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue';

  const component: DefineComponent<
    Record<string, unknown>,
    Record<string, unknown>,
    unknown
  >;

  export default component;
}

interface ImportMetaEnv {
  readonly VITE_APP_NAME: string;
  readonly VITE_REVERB_APP_KEY: string;
  readonly VITE_REVERB_HOST: string;
  readonly VITE_REVERB_PORT: string;
  readonly VITE_REVERB_SCHEME: string;
  readonly VITE_REVERB_USE_CURRENT_ORIGIN?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
