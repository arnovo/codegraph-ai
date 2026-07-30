export interface ReverbClientConfig {
  wsHost: string;
  wsPort: number;
  wssPort: number;
  forceTLS: boolean;
}

function isLocalHostname(hostname: string): boolean {
  return hostname === 'localhost' || hostname === '127.0.0.1';
}

function readWindowOriginConfig(): ReverbClientConfig {
  const secure = window.location.protocol === 'https:';
  const port = window.location.port ? Number(window.location.port) : secure ? 443 : 80;

  return {
    wsHost: window.location.hostname,
    wsPort: port,
    wssPort: port,
    forceTLS: secure,
  };
}

export function resolveReverbClientConfig(
  env: ImportMetaEnv = import.meta.env,
): ReverbClientConfig {
  const envHost = env.VITE_REVERB_HOST?.trim() ?? '';
  const envScheme = env.VITE_REVERB_SCHEME?.trim() ?? '';
  const envPort = env.VITE_REVERB_PORT?.trim() ?? '';
  const preferCurrentOrigin = env.VITE_REVERB_USE_CURRENT_ORIGIN === 'true';

  const shouldUseCurrentOrigin =
    typeof window !== 'undefined'
    && (
      preferCurrentOrigin
      || (
        isLocalHostname(envHost)
        && !isLocalHostname(window.location.hostname)
      )
    );

  if (shouldUseCurrentOrigin) {
    return readWindowOriginConfig();
  }

  const scheme = envScheme || 'http';
  const port = Number(envPort || 8080);

  return {
    wsHost: envHost || (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
  };
}
