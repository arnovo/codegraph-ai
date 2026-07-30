import DOMPurify from 'dompurify';
import { Marked } from 'marked';

const marked = new Marked({
  breaks: true,
  gfm: true,
});

marked.use({
  renderer: {
    code({ text, lang }) {
      const language = (lang ?? '').trim().toLowerCase();

      if (language === 'mermaid') {
        return `<div class="markdown-body__mermaid">${escapeHtml(text)}</div>`;
      }

      const safeLang = language.replace(/[^a-z0-9_-]/gi, '');
      const className = safeLang ? ` class="language-${safeLang}"` : '';

      return `<pre><code${className}>${escapeHtml(text)}</code></pre>`;
    },
  },
});

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export function renderMarkdown(source: string): string {
  const raw = marked.parse(source, { async: false }) as string;

  return DOMPurify.sanitize(raw, {
    USE_PROFILES: { html: true },
    ADD_ATTR: ['target', 'rel', 'class'],
  });
}
