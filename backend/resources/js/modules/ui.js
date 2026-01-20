import { APP_CONFIG } from './constants.js';

export class UIManager {
    constructor() {
        this.loadingOverlay = document.getElementById('loading-overlay');
        this.loadingTitle = document.getElementById('loading-title');
        this.loadingStatus = document.getElementById('loading-status');
        this.timerCount = document.getElementById('timer-count');
        this.timerInterval = null;
        this.seconds = 0;
    }

    startLoading(mode) {
        if (!this.loadingOverlay) return;

        this.loadingOverlay.classList.add('active');
        this.seconds = 0;
        if (this.timerCount) this.timerCount.textContent = '0';

        if (this.timerInterval) clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            this.seconds++;
            if (this.timerCount) this.timerCount.textContent = this.seconds;
        }, APP_CONFIG.TIMER_INTERVAL);

        if (mode === 'generate') {
            if (this.loadingTitle) this.loadingTitle.textContent = "問題を生成しています...";
            if (this.loadingStatus) this.loadingStatus.textContent = "過去問データベース検索中 / シナリオ構築中...";
        } else if (mode === 'score') {
            if (this.loadingTitle) this.loadingTitle.textContent = "採点しています...";
            if (this.loadingStatus) this.loadingStatus.textContent = "採点基準との照合中 / 講評作成中...";
        }
    }

    stopLoading() {
        if (!this.loadingOverlay) return;
        this.loadingOverlay.classList.remove('active');
        clearInterval(this.timerInterval);
    }

    syncSubcategories(categorySelect, subcategorySelect, categories, defaultLabels) {
        if (!categorySelect || !subcategorySelect) return;
        const selectedCategory = categorySelect.value;

        if (!selectedCategory) {
            const opt = document.createElement('option');
            opt.value = "";
            opt.textContent = defaultLabels.noSelectionLabel;
            opt.disabled = true;
            opt.selected = true;
            subcategorySelect.replaceChildren(opt);
            subcategorySelect.disabled = true;
            return;
        }

        subcategorySelect.disabled = false;
        const defaultOpt = document.createElement('option');
        defaultOpt.value = "";
        defaultOpt.textContent = defaultLabels.defaultLabel;
        subcategorySelect.replaceChildren(defaultOpt);

        if (categories && categories[selectedCategory]) {
            const subcategories = categories[selectedCategory].subcategories;
            for (const [code, label] of Object.entries(subcategories)) {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = label;
                subcategorySelect.appendChild(opt);
            }
        }
    }

    updateCharacterCounter(answerArea, countBox) {
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
}
