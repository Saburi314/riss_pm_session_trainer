import { parseMarkdown, renderMermaid } from './renderer.js';
import { APP_CONFIG } from './constants.js';

export class ExerciseRenderer {
    constructor(elements) {
        this.exerciseContent = elements.exerciseContent;
        this.exerciseCard = elements.exerciseCard;
        this.pdfCard = elements.pdfCard;
        this.scoreResultCard = elements.scoreResultCard;
        this.scoringContent = elements.scoringContent;
        this.answerCard = elements.answerCard;
        this.dynamicFormContainer = elements.dynamicFormContainer;
        this.answerArea = elements.answerArea;
        this.scoreForm = elements.scoreForm;
        this.pdfFileIdHidden = elements.pdfFileIdHidden;
    }

    renderExercise(data) {
        if (this.exerciseContent) {
            this.exerciseContent.innerHTML = parseMarkdown(data.exerciseText);
            this.exerciseCard.classList.remove('hidden');
            if (this.pdfCard) this.pdfCard.classList.add('hidden');
        }

        if (this.scoreForm) {
            this.scoreForm.querySelector('[name="category"]').value = data.category || '';
            this.scoreForm.querySelector('[name="subcategory"]').value = data.subcategory || '';
            this.scoreForm.querySelector('[name="exercise_text"]').value = data.exerciseText || '';
            if (this.pdfFileIdHidden) this.pdfFileIdHidden.value = '';

            if (this.dynamicFormContainer) this.dynamicFormContainer.innerHTML = '';
            if (this.answerArea) {
                this.answerArea.classList.remove('hidden');
                // AI演習は3問固定とする
                if (!data.pdf_file_id) {
                    this.answerArea.value = "(1)\n(2)\n(3)";
                }
            }
            if (this.answerCard) this.answerCard.classList.remove('hidden');
        }

        if (this.scoreResultCard) this.scoreResultCard.classList.add('hidden');

        renderMermaid();
    }

    renderScore(data) {
        if (!this.scoreResultCard || !this.scoringContent) return;

        const match = (data.scoringResult || '').match(/(?:Score|点数|スコア)[:：]\s*(\d+)/u);
        const scoreValue = match ? parseInt(match[1]) : 0;

        let scoreColor = APP_CONFIG.SCORING.COLORS.BRONZE;
        if (scoreValue >= APP_CONFIG.SCORING.THRESHOLDS.GOLD) {
            scoreColor = APP_CONFIG.SCORING.COLORS.GOLD;
        } else if (scoreValue >= APP_CONFIG.SCORING.THRESHOLDS.SILVER) {
            scoreColor = APP_CONFIG.SCORING.COLORS.SILVER;
        }

        this.scoreResultCard.classList.remove('hidden');

        const badge = this.scoreResultCard.querySelector('.score-badge');
        if (badge) {
            badge.style.background = scoreColor;
            badge.style.boxShadow = `0 10px 25px ${scoreColor}66`;
            badge.textContent = scoreValue;
        }

        const label = this.scoreResultCard.querySelector('.score-label');
        if (label) label.style.color = scoreColor;

        this.scoringContent.innerHTML = parseMarkdown(data.scoringResult);
        this.scoreResultCard.scrollIntoView({ behavior: 'smooth' });
    }

    renderDynamicForm(formJson, pdfId) {
        if (!this.dynamicFormContainer) return;
        this.dynamicFormContainer.innerHTML = '';

        if (!formJson || !formJson.questions) {
            // デフォルトの(1)-(5)などのプレースホルダーをセット
            if (this.answerArea) {
                this.answerArea.value = "(1)\n(2)\n(3)\n(4)\n(5)";
                this.answerArea.classList.remove('hidden');
            }
            return;
        }

        if (this.answerArea) this.answerArea.classList.add('hidden');

        formJson.questions.forEach(q => {
            const div = document.createElement('div');
            div.className = 'dynamic-question-item';

            const label = document.createElement('label');
            label.className = 'dynamic-question-label';
            label.textContent = `${q.text} ${q.limit ? `(${q.limit}字以内)` : ''}`;

            const container = document.createElement('div');
            container.className = 'dynamic-input-container';

            const input = document.createElement('textarea');
            input.className = 'dynamic-input';
            input.rows = q.limit > 50 ? 4 : 2;
            input.dataset.id = q.id;
            input.dataset.limit = q.limit || 0;

            const hint = document.createElement('div');
            hint.className = 'dynamic-limit-hint';
            hint.textContent = `0 / ${q.limit || '∞'}`;

            input.addEventListener('input', () => {
                const len = input.value.length;
                hint.textContent = `${len} / ${q.limit || '∞'}`;
                if (q.limit && len > q.limit) {
                    hint.classList.add('error');
                } else {
                    hint.classList.remove('error');
                }
            });

            container.appendChild(input);
            container.appendChild(hint);
            div.appendChild(label);
            div.appendChild(container);
            this.dynamicFormContainer.appendChild(div);
        });
    }

    getCombinedDynamicAnswers() {
        if (!this.dynamicFormContainer) return '';
        const inputs = this.dynamicFormContainer.querySelectorAll('.dynamic-input');
        let combined = '';
        inputs.forEach(input => {
            combined += `(${input.dataset.id}) ${input.value}\n`;
        });
        return combined;
    }

    /**
     * 選択された問題の解答フォームを生成
     * @param {Object} questionsData - questions.data のJSON
     * @param {Array} selectedQuestions - 選択された問題番号の配列
     * @param {Number} pdfId - PDF ID
     */
    renderQuestionAnswerForm(questionsData, selectedQuestions, pdfId) {
        if (!this.dynamicFormContainer) return;
        this.dynamicFormContainer.innerHTML = '';

        if (this.answerArea) this.answerArea.classList.add('hidden');

        if (!questionsData || !questionsData.questions) {
            console.error('Questions data not found');
            return;
        }

        selectedQuestions.forEach(qNum => {
            const question = questionsData.questions.find(q => q.question_number === qNum);
            if (!question) return;

            const questionBlock = document.createElement('div');
            questionBlock.className = 'question-block';
            questionBlock.dataset.questionNumber = qNum;

            const questionTitle = document.createElement('h3');
            questionTitle.textContent = question.title || `問${qNum}`;
            questionBlock.appendChild(questionTitle);

            // 各設問を処理
            if (question.sections && Array.isArray(question.sections)) {
                question.sections.forEach(section => {
                    const sectionBlock = document.createElement('div');
                    sectionBlock.className = 'section-block';

                    const sectionTitle = document.createElement('h4');
                    sectionTitle.textContent = `設問${section.section_id}`;
                    sectionBlock.appendChild(sectionTitle);

                    if (section.section_text) {
                        const sectionText = document.createElement('p');
                        sectionText.className = 'section-text';
                        sectionText.textContent = section.section_text;
                        sectionBlock.appendChild(sectionText);
                    }

                    // 各解答項目を処理
                    if (section.items && Array.isArray(section.items)) {
                        section.items.forEach(item => {
                            const answerItem = document.createElement('div');
                            answerItem.className = 'answer-item';

                            const label = document.createElement('label');
                            label.textContent = `${item.item_text || item.item_id}`;
                            if (item.char_limit) {
                                label.textContent += ` (${item.char_limit}字以内)`;
                            }
                            answerItem.appendChild(label);

                            const inputType = item.answer_type === 'text' && (!item.char_limit || item.char_limit <= 30) ? 'input' : 'textarea';
                            const input = document.createElement(inputType);
                            input.className = 'char-limited-input';
                            input.dataset.questionNumber = qNum;
                            input.dataset.sectionId = section.section_id;
                            input.dataset.itemId = item.item_id;

                            if (item.char_limit) {
                                input.maxLength = item.char_limit;
                                input.dataset.charLimit = item.char_limit;
                            }

                            if (inputType === 'textarea') {
                                input.rows = item.char_limit && item.char_limit <= 50 ? 2 : 3;
                            }

                            const charCounter = document.createElement('span');
                            charCounter.className = 'char-counter';
                            charCounter.textContent = `0/${item.char_limit || '∞'}`;

                            input.addEventListener('input', () => {
                                const len = input.value.length;
                                charCounter.textContent = `${len}/${item.char_limit || '∞'}`;
                                if (item.char_limit && len > item.char_limit) {
                                    charCounter.classList.add('over-limit');
                                } else {
                                    charCounter.classList.remove('over-limit');
                                }
                            });

                            answerItem.appendChild(input);
                            answerItem.appendChild(charCounter);
                            sectionBlock.appendChild(answerItem);
                        });
                    }

                    questionBlock.appendChild(sectionBlock);
                });
            }

            this.dynamicFormContainer.appendChild(questionBlock);
        });
    }
}
