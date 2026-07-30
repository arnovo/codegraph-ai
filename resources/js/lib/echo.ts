import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { McpStatus } from '@/types/chat';
import { resolveReverbClientConfig } from '@/lib/reverbClientConfig';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

const reverbConfig = resolveReverbClientConfig();

export const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY as string,
  wsHost: reverbConfig.wsHost,
  wsPort: reverbConfig.wsPort,
  wssPort: reverbConfig.wssPort,
  wsPath: '/app',
  forceTLS: reverbConfig.forceTLS,
  enabledTransports: ['ws', 'wss'],
});

export function subscribeMcpStatus(onUpdate: (status: McpStatus) => void): () => void {
  const channel = echo.channel('mcp-status');
  channel.listen('.status.updated', (payload: McpStatus) => onUpdate(payload));

  return () => {
    echo.leave('mcp-status');
  };
}

export function bindEchoConnectionHandlers(
  onConnected: () => void,
  onDisconnected: () => void,
): () => void {
  const connection = echo.connector.pusher.connection;

  connection.bind('connected', onConnected);
  connection.bind('disconnected', onDisconnected);
  connection.bind('unavailable', onDisconnected);
  connection.bind('failed', onDisconnected);

  if (connection.state === 'connected') {
    onConnected();
  }

  return () => {
    connection.unbind('connected', onConnected);
    connection.unbind('disconnected', onDisconnected);
    connection.unbind('unavailable', onDisconnected);
    connection.unbind('failed', onDisconnected);
  };
}
