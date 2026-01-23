import { APP_CONFIG } from './constants.js';

export class UIManager {
    constructor() {
        this.loadingOverlay = document.getElementById('loading-overlay');
        this.loadingTitle = document.getElementById('loading-title');
        this.loadingStatus = document.getElementById('loading-status');
        this.timerCount = document.getElementById('timer-count');
        this.progressBar = document.getElementById('loading-progress-bar');
        this.progressPercent = document.getElementById('loading-progress-percent');
        this.timerInterval = null;
        this.progressInterval = null;
        this.seconds = 0;
        this.progress = 0;
    }

    startLoading(mode) {
        if (!this.loadingOverlay) return;

        this.progressBar = this.progressBar || document.getElementById('loading-progress-bar');
        this.progressPercent = this.progressPercent || document.getElementById('loading-progress-percent');

        this.loadingOverlay.classList.add('active');
        this.seconds = 0;
        this.progress = 0;
        this.mode = mode; // 保持
        if (this.timerCount) this.timerCount.textContent = '0';
        this.updateProgress(0);

        if (this.timerInterval) clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            this.seconds++;
            if (this.timerCount) this.timerCount.textContent = this.seconds;
        }, APP_CONFIG.TIMER_INTERVAL);

        if (this.progressInterval) clearInterval(this.progressInterval);
        this.progressInterval = setInterval(() => {
            this.simulateProgress();
        }, APP_CONFIG.PROGRESS_UPDATE_INTERVAL);

        this.updateLoadingTitle(mode);
    }

    updateLoadingTitle(mode) {
        if (!this.loadingTitle) return;
        if (mode === 'generate') {
            this.loadingTitle.textContent = "問題を生成しています...";
        } else if (mode === 'score') {
            this.loadingTitle.textContent = "採点しています...";
        }
    }

    stopLoading() {
        if (!this.loadingOverlay) return;

        // 100%まで滑らかに飛ばす
        clearInterval(this.progressInterval);
        const finishStep = (100 - this.progress) / 10;
        let count = 0;

        const finishInterval = setInterval(() => {
            this.progress += finishStep;
            this.updateProgress(Math.min(this.progress, 100));
            count++;

            if (count >= 10 || this.progress >= 100) {
                clearInterval(finishInterval);
                setTimeout(() => {
                    this.loadingOverlay.classList.remove('active');
                    clearInterval(this.timerInterval);
                }, 400);
            }
        }, 50);
    }

    simulateProgress() {
        const stages = APP_CONFIG?.PROGRESS_STAGES;
        if (!stages) return;

        let currentStage = null;

        if (this.progress < stages.SEARCH.max) {
            currentStage = stages.SEARCH;
        } else if (this.progress < stages.ANALYZE.max) {
            currentStage = stages.ANALYZE;
        } else if (this.progress < stages.DRAFT.max) {
            currentStage = stages.DRAFT;
        } else {
            currentStage = stages.FINALIZE;
        }

        // 基本の増分 (ランダム性を加味)
        let increment = Math.random() * currentStage.speed;

        // 漸近的ロジック: 100%に近づくほど増分にブレーキをかける
        const remaining = 100 - this.progress;
        const asymptoticFactor = Math.max(remaining / 80, 0.1);
        increment *= asymptoticFactor;

        // 最低速度保証 (1回の更新で少なくとも0.05%は進む)
        increment = Math.max(increment, 0.05);

        if (this.loadingStatus && currentStage) {
            const text = currentStage.text[this.mode] || currentStage.text.generate;
            this.loadingStatus.textContent = text;
        }

        this.progress += increment;

        // 99%を超えたら極限まで減速
        if (this.progress >= 99.5) {
            this.progress = 99.5;
        }

        this.updateProgress(this.progress);
    }

    updateProgress(val) {
        const rounded = Math.floor(val);
        if (this.progressBar) this.progressBar.style.width = `${rounded}%`;
        if (this.progressPercent) this.progressPercent.textContent = `${rounded}%`;
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
            const start = m.index + m[0].length;
            const end = matches[i + 1] ? matches[i + 1].index : val.length;
            const len = val.substring(start, end).trim().length;

            const span = document.createElement('span');
            span.className = 'counter-tag';
            span.textContent = `(${num}): ${len} 文字`;
            countBox.appendChild(span);
        });
    }
}
