import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';

mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose' });
window.mermaid = mermaid;

document.addEventListener('DOMContentLoaded', () => {
    // データはBlade側で window.RissApp に注入されていると想定
    const { categories, currentSubcategory, exerciseRaw, scoringRaw } = window.RissApp || {};

    const categorySelect = document.getElementById('category');
    const subcategorySelect = document.getElementById('subcategory');

    // 小分類の選択肢を同期する（Categoryを選択するとSubcategoryの選択肢が更新される）
    function syncSubcategoryList() {
        if (!categorySelect || !subcategorySelect) return;
        const selectedCategory = categorySelect.value;

        if (!selectedCategory) {
            const opt = document.createElement('option');
            opt.value = "";
            opt.textContent = "（選択不要）";
            subcategorySelect.replaceChildren(opt);
            subcategorySelect.disabled = true;
            return;
        }

        subcategorySelect.disabled = false;
        const defaultOpt = document.createElement('option');
        defaultOpt.value = "";
        defaultOpt.textContent = "ランダム/全般";
        subcategorySelect.replaceChildren(defaultOpt);

        if (categories && categories[selectedCategory]) {
            const subcategories = categories[selectedCategory].subcategories;
            for (const [key, label] of Object.entries(subcategories)) {
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = label;
                if (key === currentSubcategory) opt.selected = true;
                subcategorySelect.appendChild(opt);
            }
        }
    }

    if (categorySelect) categorySelect.addEventListener('change', syncSubcategoryList);
    syncSubcategoryList();

    const answerArea = document.getElementById('user_answer');
    const countBox = document.getElementById('segment-counters');

    // 文字数カウント（設問4のみ文字数をカウント）
    function refreshCharacterCounter() {
        if (!answerArea || !countBox) return;
        const val = answerArea.value;
        const matches = [...val.matchAll(/\((\d+)\)/g)];
        countBox.textContent = '';

        matches.forEach((m, i) => {
            const num = m[1];
            // 設問1, 3, 4, 5の文字数をカウント
            if (['1', '3', '4', '5'].includes(num)) {
                const start = m.index + m[0].length;
                const end = matches[i + 1] ? matches[i + 1].index : val.length;
                const len = val.substring(start, end).trim().length;

                const span = document.createElement('span');
                span.className = 'counter-tag';
                span.textContent = `(${num}): ${len} 文字`;
                countBox.appendChild(span);
            }
        });
    }

    if (answerArea) {
        answerArea.addEventListener('input', refreshCharacterCounter);
        refreshCharacterCounter();
    }

    // AIの書き漏らしを補完するセーフティネット
    function robustPreprocess(text) {
        if (!text) return '';
        // ヘッダー直後に改行がない場合の補完（空行を確保して見やすくする）
        text = text.replace(/(【問題文】|【設問】)([^\s\n])/g, '$1\n\n$2');

        let lines = text.split('\n');
        let result = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            let trimmed = line.trim();

            // テーブル行のクリーニング
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

    // コンテンツの表示初期化
    function initializeDisplay() {
        const exerciseContent = document.getElementById('exercise-content');
        const scoringContent = document.getElementById('scoring-content');

        if (exerciseContent && exerciseRaw) {
            // カスタムレンダラー（Mermaid対応）
            const renderer = new marked.Renderer();
            // Marked v4+ の引数仕様に合わせる
            renderer.code = function (args) {
                const text = typeof args === 'string' ? args : args.text;
                const language = typeof args === 'string' ? arguments[1] : args.lang;

                if (language === 'mermaid') {
                    return `<div class="mermaid">${text}</div>`;
                }
                return `<pre><code class="language-${language}">${text}</code></pre>`;
            };

            // セーフティネットを通してパース
            const robustText = robustPreprocess(exerciseRaw);
            // [a] の強調（太字があってもなくても対応）
            const highlighted = robustText.replace(/(\*\*)?［\s*([a-z])\s*］(\*\*)?/g, '<span class="blank-marker">［ $2 ］</span>');
            exerciseContent.innerHTML = marked.parse(highlighted, {
                renderer: renderer,
                breaks: true // GFM-like line breaks
            });
        }

        if (scoringContent && scoringRaw) {
            scoringContent.innerHTML = marked.parse(scoringRaw, { breaks: true });
        }

        // Mermaid図の描画
        if (typeof mermaid !== 'undefined') {
            setTimeout(() => {
                mermaid.run({
                    querySelector: '.mermaid'
                }).catch(err => console.error('Mermaid error:', err));
            }, 100);
        }
    }

    initializeDisplay();
});
