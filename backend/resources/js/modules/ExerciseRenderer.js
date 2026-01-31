import { parseMarkdown, renderMermaid } from './renderer.js';
import { APP_CONFIG } from './constants.js';

console.log("ExerciseRenderer Version JS-V18-TABBED-UI Loaded");

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
        this.pastPaperIdHidden = elements.pastPaperIdHidden;
        this.pdfViewerContainer = document.getElementById('pdf-viewer-container');
        this.splitContainer = document.getElementById('exercise-split-container');
        this.questionTabs = document.getElementById('past-paper-tabs');
    }

    async renderPdf(pdfUrl) {
        if (!this.pdfViewerContainer) return;
        this.pdfViewerContainer.innerHTML = '<div class="p-8 text-center"><div class="spinner"></div><p>PDFを読み込み中...</p></div>';

        try {
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            const pdf = await loadingTask.promise;
            this.pdfViewerContainer.innerHTML = ''; // Clear loading

            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);

                // Adjust scale for quality (Larger scale for wide screens)
                const viewport = page.getViewport({ scale: 2.0 });

                const canvas = document.createElement('canvas');
                canvas.className = 'shadow-md mb-0'; // Removed mx-auto, set mb-0
                canvas.style.display = 'block';
                canvas.style.maxWidth = '100%';
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const context = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                this.pdfViewerContainer.appendChild(canvas);
                await page.render(renderContext).promise;
            }
        } catch (error) {
            console.error('Error rendering PDF:', error);
            this.pdfViewerContainer.innerHTML = `<div class="p-8 text-red-500 font-bold">PDFの表示に失敗しました。ご不便をおかけします。 (Error: ${error.message})</div>`;
        }
    }

    formatAiText(text) {
        if (!text) return '';
        // Highly aggressive spacing: Ensure every logical block has breathing room
        return text
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .join('\n\n'); // Force double newlines between everything
    }

    renderExercise(data) {
        if (this.splitContainer) this.splitContainer.classList.remove('hidden');

        if (this.exerciseContent) {
            // Guarantee double spacing for readability if it's AI generated (no pdf_file_id)
            const formattedText = data.pdf_file_id ? data.exerciseText : this.formatAiText(data.exerciseText);
            this.exerciseContent.innerHTML = parseMarkdown(formattedText);
            this.exerciseCard.classList.remove('hidden');
            // Ensure paper-mode is applied for AI generated content too
            this.exerciseCard.classList.add('paper-mode');
            if (this.pdfCard) this.pdfCard.classList.add('hidden');
        }

        if (this.scoreForm) {
            this.scoreForm.querySelector('[name="category"]').value = data.category || '';
            this.scoreForm.querySelector('[name="subcategory"]').value = data.subcategory || '';
            this.scoreForm.querySelector('[name="exercise_text"]').value = data.exerciseText || '';
            if (this.pastPaperIdHidden) this.pastPaperIdHidden.value = '';

            if (this.dynamicFormContainer) this.dynamicFormContainer.innerHTML = '';
            if (this.answerArea) {
                this.answerArea.classList.remove('hidden');
                // AI演習は3問固定とする
                if (!data.pdf_file_id) {
                    this.answerArea.value = "(1)\n\n(2)\n\n(3)";
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
                this.answerArea.value = "(1)\n\n(2)\n\n(3)\n\n(4)\n\n(5)";
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

        const answerMap = new Map();

        // Find all question blocks (tab panes)
        const questionPanes = this.dynamicFormContainer.querySelectorAll('.tab-pane');

        let result = '';

        questionPanes.forEach(pane => {
            const qNum = pane.dataset.questionNumber;
            const qAnswers = [];

            // Process text/select/textarea in this pane
            pane.querySelectorAll('textarea.dynamic-input, select.dynamic-input, input[type="text"].dynamic-input').forEach(el => {
                if (el.value && el.value.trim()) {
                    qAnswers.push(`(${el.dataset.itemId}) ${el.value.trim()}`);
                }
            });

            // Process radio/checkbox in this pane
            const choiceGroups = new Map();
            pane.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked').forEach(el => {
                const itemId = el.dataset.itemId;
                if (!choiceGroups.has(itemId)) choiceGroups.set(itemId, []);
                choiceGroups.get(itemId).push(el.value);
            });

            for (const [itemId, values] of choiceGroups) {
                qAnswers.push(`(${itemId}) ${values.join(', ')}`);
            }

            if (qAnswers.length > 0) {
                result += `問${qNum}\n${qAnswers.join('\n')}\n\n`;
            }
        });

        return result.trim();
    }

    renderQuestionAnswerForm(questionsData, selectedQuestionsIgnored, pdfId) {
        if (this.splitContainer) this.splitContainer.classList.remove('hidden');
        if (!this.dynamicFormContainer || !this.questionTabs) return;

        this.dynamicFormContainer.innerHTML = '';
        this.questionTabs.innerHTML = '';
        this.questionTabs.classList.remove('hidden');

        if (this.answerArea) this.answerArea.classList.add('hidden');

        if (!questionsData || !questionsData.questions) {
            console.error('Questions data not found');
            return;
        }

        const questions = questionsData.questions;

        questions.forEach((question, index) => {
            const qNum = question.question_number;

            // 1. Tab Button
            const tabBtn = document.createElement('button');
            tabBtn.className = `question-tab ${index === 0 ? 'active' : ''}`;
            tabBtn.textContent = question.title || `問${qNum}`;
            tabBtn.dataset.target = `q-pane-${qNum}`;
            tabBtn.addEventListener('click', () => {
                this.questionTabs.querySelectorAll('.question-tab').forEach(t => t.classList.remove('active'));
                tabBtn.classList.add('active');

                this.dynamicFormContainer.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                document.getElementById(`q-pane-${qNum}`).classList.add('active');
            });
            this.questionTabs.appendChild(tabBtn);

            // 2. Tab Pane
            const pane = document.createElement('div');
            pane.id = `q-pane-${qNum}`;
            pane.className = `tab-pane ${index === 0 ? 'active' : ''}`;
            pane.dataset.questionNumber = qNum;

            const questionTitle = document.createElement('h3');
            questionTitle.className = 'text-lg font-bold mb-6 text-black border-b pb-2';
            questionTitle.textContent = question.title || `問${qNum}`;
            pane.appendChild(questionTitle);

            if (question.sections && Array.isArray(question.sections)) {
                question.sections.forEach(section => {
                    const sectionBlock = document.createElement('div');
                    sectionBlock.className = 'section-block mb-8';

                    if (section.section_text) {
                        const sectionText = document.createElement('div');
                        sectionText.className = 'section-text mb-4 text-black text-[1.1rem] leading-relaxed font-medium whitespace-pre-line';
                        sectionText.textContent = section.section_text;
                        sectionBlock.appendChild(sectionText);
                    }

                    if (section.items && Array.isArray(section.items)) {
                        const itemCount = section.items.length;
                        section.items.forEach(item => {
                            const answerItem = document.createElement('div');
                            answerItem.className = 'answer-item mb-6 pl-4 border-l-2 border-gray-100';

                            const answerType = item.answer_type || 'text';
                            const isChoiceItem = (answerType === 'radio' || answerType === 'checkbox');

                            const shouldShowLabel = !(itemCount === 1 && item.item_id === '1');

                            if (shouldShowLabel) {
                                const label = document.createElement('div');
                                label.className = 'font-bold text-black mb-2 text-base';
                                label.textContent = item.item_text || item.item_id;
                                answerItem.appendChild(label);
                            }

                            const inputContainer = document.createElement('div');
                            inputContainer.className = 'relative mt-1';

                            if (isChoiceItem) {
                                const choicesWrapper = document.createElement('div');
                                choicesWrapper.className = 'bg-gray-50 p-4 rounded-lg';

                                const choicesHeader = document.createElement('div');
                                choicesHeader.className = 'text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider';
                                choicesHeader.textContent = '解答群';
                                choicesWrapper.appendChild(choicesHeader);

                                const choicesContainer = document.createElement('div');
                                choicesContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-2';

                                const choices = (item.choices || ['ア', 'イ', 'ウ', 'エ']).filter(c => c && c.trim().length > 0 && c.trim() !== item.item_id);
                                const nameGroup = `q${qNum}_s${section.section_id}_i${item.item_id}`;

                                choices.forEach(choice => {
                                    const choiceLabel = document.createElement('label');
                                    choiceLabel.className = 'cursor-pointer flex items-start p-2 hover:bg-white hover:shadow-sm transition-all text-black rounded border border-transparent';

                                    const input = document.createElement('input');
                                    input.type = answerType;
                                    input.name = nameGroup;
                                    input.value = choice.split(':')[0];
                                    input.className = 'dynamic-input h-4 w-4 mt-1 mr-2';
                                    input.dataset.itemId = item.item_id;
                                    input.dataset.sectionId = section.section_id;
                                    input.dataset.questionNumber = qNum;

                                    const span = document.createElement('span');
                                    span.className = 'text-base';
                                    span.textContent = choice;

                                    input.addEventListener('change', () => {
                                        if (input.checked) {
                                            choiceLabel.classList.add('bg-white', 'shadow-sm', 'border-gray-200');
                                        } else {
                                            choiceLabel.classList.remove('bg-white', 'shadow-sm', 'border-gray-200');
                                        }
                                    });

                                    choiceLabel.appendChild(input);
                                    choiceLabel.appendChild(span);
                                    choicesContainer.appendChild(choiceLabel);
                                });
                                choicesWrapper.appendChild(choicesContainer);
                                inputContainer.appendChild(choicesWrapper);

                            } else {
                                const isLong = !item.char_limit || item.char_limit > 50;
                                const input = document.createElement(isLong ? 'textarea' : 'input');
                                if (!isLong) input.type = 'text';

                                input.className = 'dynamic-input block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-base p-3 border text-black font-medium';
                                if (isLong) input.rows = 3;

                                input.dataset.itemId = item.item_id;
                                input.dataset.sectionId = section.section_id;
                                input.dataset.questionNumber = qNum;

                                if (item.char_limit) input.maxLength = item.char_limit;

                                inputContainer.appendChild(input);

                                const counterDiv = document.createElement('div');
                                counterDiv.className = 'text-right mt-1 text-xs text-gray-400 font-mono';
                                const limitText = item.char_limit ? ` / ${item.char_limit}` : '';
                                counterDiv.textContent = `0${limitText} 文字`;

                                input.addEventListener('input', () => {
                                    const len = input.value.length;
                                    counterDiv.textContent = `${len}${limitText} 文字`;
                                    if (item.char_limit && len >= item.char_limit) {
                                        counterDiv.classList.add('text-red-500', 'font-bold');
                                    } else {
                                        counterDiv.classList.remove('text-red-500', 'font-bold');
                                    }
                                });
                                inputContainer.appendChild(counterDiv);
                            }

                            answerItem.appendChild(inputContainer);
                            sectionBlock.appendChild(answerItem);
                        });
                    }
                    pane.appendChild(sectionBlock);
                });
            }
            this.dynamicFormContainer.appendChild(pane);
        });
    }
}
