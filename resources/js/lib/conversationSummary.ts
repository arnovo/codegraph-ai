import type { ChatMessage, ConversationSummary } from '@/types/chat';

export function countSummaryMessages(messages: ChatMessage[]): number {
  return messages.filter((message) => message.role === 'user' || message.role === 'assistant').length;
}

export function isSummaryStale(conversation: ConversationSummary | null | undefined): boolean {
  if (!conversation?.summary || conversation.summary_message_count == null) {
    return false;
  }

  return conversation.messages_count > conversation.summary_message_count;
}
