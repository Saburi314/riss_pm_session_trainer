import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';

mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose' });
window.mermaid = mermaid;

document.addEventListener('DOMContentLoaded', () => {
    const { categories, currentSubcategory } = window.RissApp || {};

    const categorySelect = document.getElementById('category');
    const subcategorySelect = document.getElementById('subcategory');
    const generateForm = document.getElementById('form-generate');
    const scoreForm = document.getElementById('form-score');

    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingTitle = document.getElementById('loading-title');
    const loadingStatus = document.getElementById('loading-status');
    const timerCount = document.getElementById('timer-count');
    const triviaText = document.getElementById('trivia-text');

    let timerInterval = null;
    let triviaInterval = null;
    let seconds = 0;
    let triviaList = [];
    let currentTriviaIndex = 0;


    /**
     * ローディング画面を開始する
     * @param {string} mode - 'generate' | 'score'
     */
    async function startLoading(mode) {
        if (!loadingOverlay) return;

        triviaList = [];
        currentTriviaIndex = 0;
        if (triviaText) triviaText.textContent = "セキュリティ豆知識を読み込み中...";

        loadingOverlay.style.display = 'flex';
        seconds = 0;
        if (timerCount) timerCount.textContent = '0';

        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            seconds++;
            if (timerCount) timerCount.textContent = seconds;
        }, 1000);

        const category = categorySelect ? categorySelect.value : '';
        await fetchTriviaList(category);

        showNextTrivia();

        // Mode specific settings
        if (mode === 'generate') {
            if (loadingTitle) loadingTitle.textContent = "問題を生成しています...";
            if (loadingStatus) loadingStatus.textContent = "過去問データベース検索中 / シナリオ構築中...";
        } else if (mode === 'score') {
            if (loadingTitle) loadingTitle.textContent = "採点しています...";
            if (loadingStatus) loadingStatus.textContent = "採点基準との照合中 / 講評作成中...";
        } else {
            if (loadingTitle) loadingTitle.textContent = "処理中...";
            if (loadingStatus) loadingStatus.textContent = "お待ちください...";
        }

        if (triviaInterval) clearInterval(triviaInterval);
        triviaInterval = setInterval(showNextTrivia, 15000);
    }

    function stopLoading() {
        if (!loadingOverlay) return;
        loadingOverlay.style.display = 'none';
        clearInterval(timerInterval);
        clearInterval(triviaInterval);
    }

    async function fetchTriviaList(category) {
        try {
            const params = new URLSearchParams();
            if (category) params.append('category', category);

            const url = `/api/trivia/random-list?${params.toString()}`;
            const response = await fetch(url);
            if (response.ok) {
                triviaList = await response.json();
            }
        } catch (e) {
            console.error('Trivia list fetch error:', e);
            triviaList = [];
        }
    }

    function showNextTrivia() {
        if (!triviaText) return;

        if (triviaList && triviaList.length > 0) {
            const item = triviaList[currentTriviaIndex];
            triviaText.textContent = item.content || "セキュリティ意識を高めましょう。";
            currentTriviaIndex = (currentTriviaIndex + 1) % triviaList.length;
        } else {
            triviaText.textContent = "セキュリティ豆知識をご案内します。しばらくお待ちください。";
        }
    }

    if (generateForm) {
        generateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await startLoading('generate');

            try {
                const formData = new FormData(generateForm);
                const response = await fetch(generateForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();
                renderExerciseResult(data);

            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                stopLoading();
            }
        });
    }

    if (scoreForm) {
        scoreForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await startLoading('score');

            try {
                const formData = new FormData(scoreForm);
                const response = await fetch(scoreForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();
                renderScoreResult(data);

            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                stopLoading();
            }
        });
    }


    function renderExerciseResult(data) {
        const contentDiv = document.getElementById('exercise-content');
        if (contentDiv) {
            contentDiv.innerHTML = parseMarkdown(data.exerciseText);
            const exerciseCard = document.getElementById('exercise-card');
            if (exerciseCard) exerciseCard.style.display = 'block';
        }

        const form = document.getElementById('form-score');
        if (form) {
            form.querySelector('[name="category"]').value = data.category || '';
            form.querySelector('[name="subcategory"]').value = data.subcategory || '';
            form.querySelector('[name="exercise_text"]').value = data.exerciseText || '';

            const answerCard = document.getElementById('answer-card');
            if (answerCard) answerCard.style.display = 'block';
        }

        const scoreResultCard = document.querySelector('.score-badge')?.closest('.card');
        if (scoreResultCard) {
            scoreResultCard.style.display = 'none';
        }

        renderMermaid();
    }

    function renderScoreResult(data) {
        let scoreContainer = document.getElementById('scoring-content');
        if (!scoreContainer) {
            const exerciseCard = document.getElementById('exercise-content').closest('.card')
                .nextElementSibling;

            const resultHtml = `
            <div id="score-result-card" class="card" style="text-align: center; border-top: 8px solid #4a90e2; padding-top: 40px;">
              <div id="score-badge-container"></div>
              <div class="score-label" style="font-weight: 800; transform: translateY(-10px); color: #4a90e2;">Result Score</div>
              <div id="scoring-content" class="markdown-body" style="text-align: left; margin-top: 40px;"></div>
            </div>`;

            exerciseCard.insertAdjacentHTML('afterend', resultHtml);
            scoreContainer = document.getElementById('scoring-content');
        }

        const match = (data.scoringResult || '').match(/点数：(\d+)/);
        const scoreValue = match ? parseInt(match[1]) : 0;
        const scoreColor = scoreValue >= 80 ? '#48bb78' : (scoreValue >= 60 ? '#ecc94b' : '#f6ad55');

        const scoreCard = scoreContainer.closest('.card');
        scoreCard.style.display = 'block';

        let badge = scoreCard.querySelector('.score-badge');
        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'score-badge';
            scoreCard.insertBefore(badge, scoreCard.querySelector('.score-label'));
        }

        badge.style.background = scoreColor;
        badge.style.boxShadow = `0 10px 25px ${scoreColor}66`;
        badge.textContent = scoreValue;

        const label = scoreCard.querySelector('.score-label');
        if (label) label.style.color = scoreColor;

        scoreContainer.innerHTML = parseMarkdown(data.scoringResult);
        scoreCard.scrollIntoView({ behavior: 'smooth' });
    }

    function parseMarkdown(text) {
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

        // URLなどの自動リンクを無効化（テキストとして表示）
        renderer.link = function (href, title, text) {
            return text;
        };

        const robustText = robustPreprocess(text);
        const highlighted = robustText.replace(/(\*\*)?［\s*([a-z])\s*］(\*\*)?/g, '<span class="blank-marker">［ $2 ］</span>');

        return marked.parse(highlighted, { renderer: renderer, breaks: true });
    }

    function renderMermaid() {
        if (typeof mermaid !== 'undefined') {
            setTimeout(() => {
                mermaid.run({ querySelector: '.mermaid' })
                    .catch(err => console.error('Mermaid error:', err));
            }, 100);
        }
    }

    function syncSubcategoryList() {
        if (!categorySelect || !subcategorySelect) return;
        const selectedCategory = categorySelect.value;

        if (!selectedCategory) {
            const opt = document.createElement('option');
            opt.value = "";
            opt.textContent = "選択不要";
            opt.disabled = true;
            opt.selected = true;
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

    function refreshCharacterCounter() {
        if (!answerArea || !countBox) return;
        const val = answerArea.value;
        const matches = [...val.matchAll(/\((\d+)\)/g)];
        countBox.textContent = '';

        matches.forEach((m, i) => {
            const num = m[1];
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

    function robustPreprocess(text) {
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

    function initializeDisplay() {
        const { exerciseRaw, scoringRaw } = window.RissApp || {};
        if (exerciseRaw) {
            const contentDiv = document.getElementById('exercise-content');
            if (contentDiv) contentDiv.innerHTML = parseMarkdown(exerciseRaw);
        }
        if (scoringRaw) {
            const scoreDiv = document.getElementById('scoring-content');
            if (scoreDiv) scoreDiv.innerHTML = parseMarkdown(scoringRaw);
        }
        renderMermaid();
    }

    initializeDisplay();
});
