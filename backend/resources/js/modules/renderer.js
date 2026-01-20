import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
import { APP_CONFIG } from './constants.js';

mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose' });

export function robustPreprocess(text) {
    if (!text) return '';
    text = text.replace(/(【問題文】|【設問】)([^\s\n])/g, '$1\n\n$2');
    let lines = text.split('\n');
    let result = [];
    for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        let trimmed = line.trim();
        if (trimmed.startsWith('|')) {
            const content = trimmed.replace(/[\||\s|.|-]/g, '');
            if (content === '' && !trimmed.includes('---|') && !trimmed.includes('---')) {
                continue;
            }
        }
        result.push(line);
    }
    return result.join('\n');
}

export function parseMarkdown(text) {
    if (!text) return '';
    const renderer = new marked.Renderer();
    renderer.code = function (args) {
        const codeText = typeof args === 'string' ? args : args.text;
        const language = typeof args === 'string' ? arguments[1] : args.lang;

        if (language === 'mermaid') {
            return `<div class="mermaid">${codeText}</div>`;
        }
        return `<pre><code class="language-${language}">${codeText}</code></pre>`;
    };

    renderer.link = function (href, title, text) {
        return text;
    };

    const robustText = robustPreprocess(text);
    const highlighted = robustText.replace(/(\*\*)?［\s*([a-z])\s*］(\*\*)?/g, '<span class="blank-marker">［ $2 ］</span>');

    return marked.parse(highlighted, { renderer: renderer, breaks: true });
}

export function renderMermaid() {
    if (typeof mermaid !== 'undefined') {
        setTimeout(() => {
            mermaid.run({ querySelector: '.mermaid' })
                .catch(err => console.error('Mermaid error:', err));
        }, APP_CONFIG.MERMAID_RENDER_DELAY);
    }
}
