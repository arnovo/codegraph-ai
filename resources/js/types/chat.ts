export interface ProjectSummary {
  name: string;
  root_path: string;
  display_name?: string;
  primary_stack?: string;
  nodes: number;
  edges: number;
  size_bytes: number;
}

export interface ConversationSummary {
  id: string;
  title: string;
  primary_project_name: string | null;
  summary: string | null;
  summary_message_count: number | null;
  messages_count: number;
  updated_at: string | null;
}

export interface McpStatus {
  status: string;
  checked_at: string;
  ui_url: string;
  message?: string | null;
}

export interface ChatMessage {
  id?: string;
  role: 'user' | 'assistant' | 'tool';
  content: string;
  metadata?: {
    tools?: Array<{ name: string; arguments?: Record<string, unknown>; result_summary?: string }>;
    citations?: Array<{ file: string; line?: number; symbol?: string }>;
    model?: string;
    provider?: string;
    label?: string;
  };
}

export interface StreamChunk {
  type: string;
  content?: string;
  meta?: Record<string, unknown>;
}

export interface ChatInsightsData {
  scope: {
    project_name: string | null;
    generated_at: string;
  };
  activity: {
    total_user_questions: number;
    project_user_questions: number;
    questions_last_7_days: number;
    questions_last_30_days: number;
    conversations_this_week: number;
    messages_by_day: Array<{ date: string; count: number }>;
  };
  projects: {
    top_by_questions: Array<{ name: string; display_name: string; question_count: number }>;
    active_project_share_percent: number | null;
  };
  frequent_questions: Array<{ text: string; count: number }>;
  tools: {
    by_name: Array<{ name: string; count: number }>;
    top_search_queries: Array<{ query: string; count: number }>;
  };
  citations: {
    top_files: Array<{ file: string; count: number }>;
  };
  models: {
    top_by_usage: Array<{ model: string; count: number }>;
  };
}
