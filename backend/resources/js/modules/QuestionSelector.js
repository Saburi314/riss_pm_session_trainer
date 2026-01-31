/**
 * QuestionSelector - 過去問の問題選択UI管理
 */
export class QuestionSelector {
    constructor(container) {
        this.container = container;
        this.selectedQuestions = new Set();
        this.questionsData = null;
        this.onConfirm = null;
    }

    /**
     * 問題選択UIを表示
     * @param {Object} questionsData - questions.data のJSON
     * @param {Array} solvedQuestions - 解答済みの問題番号配列
     */
    render(questionsData, solvedQuestions = []) {
        this.questionsData = questionsData;
        this.selectedQuestions.clear();

        const html = `
            <div class="question-selector-card card">
                <h2 class="section-header mb-16">
                    <span class="indicator primary"></span>
                    解答する問題を選択してください
                </h2>
                <p class="text-sm mb-16" style="color: var(--text-secondary);">
                    解答したい問題にチェックを入れて、「選択した問題を開始」ボタンを押してください。
                </p>
                <div class="question-checkboxes mb-24">
                    ${this.renderQuestionCheckboxes(questionsData, solvedQuestions)}
                </div>
                <div class="text-center">
                    <button id="btn-start-selected-questions" class="btn-large secondary" disabled>
                        選択した問題を開始
                    </button>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
        this.attachEventListeners();
    }

    renderQuestionCheckboxes(questionsData, solvedQuestions) {
        if (!questionsData || !questionsData.questions) {
            return '<p class="text-sm">問題データが見つかりません</p>';
        }

        return questionsData.questions.map(q => {
            const isSolved = solvedQuestions.includes(q.question_number);
            const solvedBadge = isSolved ? '<span class="solved-badge">✓ 解答済み</span>' : '';
            
            return `
                <label class="question-checkbox-label ${isSolved ? 'solved' : ''}">
                    <input 
                        type="checkbox" 
                        class="question-checkbox" 
                        value="${q.question_number}"
                        data-question-number="${q.question_number}"
                    >
                    <span class="question-title">
                        ${q.title || `問${q.question_number}`}
                        ${solvedBadge}
                    </span>
                </label>
            `;
        }).join('');
    }

    attachEventListeners() {
        const checkboxes = this.container.querySelectorAll('.question-checkbox');
        const startButton = this.container.querySelector('#btn-start-selected-questions');

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const qNum = parseInt(cb.value);
                if (cb.checked) {
                    this.selectedQuestions.add(qNum);
                } else {
                    this.selectedQuestions.delete(qNum);
                }
                startButton.disabled = this.selectedQuestions.size === 0;
            });
        });

        startButton.addEventListener('click', () => {
            if (this.selectedQuestions.size > 0 && this.onConfirm) {
                this.onConfirm(Array.from(this.selectedQuestions).sort((a, b) => a - b));
            }
        });
    }

    /**
     * 問題選択確定時のコールバックを設定
     */
    setOnConfirm(callback) {
        this.onConfirm = callback;
    }

    /**
     * 選択UIを非表示
     */
    hide() {
        this.container.innerHTML = '';
        this.container.classList.add('hidden');
    }

    /**
     * 選択UIを表示
     */
    show() {
        this.container.classList.remove('hidden');
    }
}
