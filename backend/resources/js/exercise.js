import { UIManager } from './modules/ui.js';

// PDF.js worker configuration
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}
import { TriviaManager } from './modules/trivia.js';
import { renderMermaid } from './modules/renderer.js';
import * as api from './modules/api.js';
import { APP_CONFIG } from './modules/constants.js';
import { PastPaperSelector } from './modules/PastPaperSelector.js';
import { ExerciseRenderer } from './modules/ExerciseRenderer.js';

document.addEventListener('DOMContentLoaded', () => {
    const { mode, categories, pastPapers, currentSubcategory, defaultLabel, noSelectionLabel, exerciseRaw, scoringRaw } = window.RissApp || {};

    const ui = new UIManager();
    const trivia = new TriviaManager(document.getElementById('trivia-text'));

    const elements = {
        categorySelect: document.getElementById('category'),
        subcategorySelect: document.getElementById('subcategory'),
        selectYear: document.getElementById('select-year'),
        selectSeason: document.getElementById('select-season'),
        selectPeriod: document.getElementById('select-period'),
        btnLoadPaper: document.getElementById('btn-load-paper'),
        generateForm: document.getElementById('form-generate'),
        scoreForm: document.getElementById('form-score'),
        answerArea: document.getElementById('user_answer'),
        countBox: document.getElementById('segment-counters'),
        dynamicFormContainer: document.getElementById('dynamic-form-container'),
        exerciseContent: document.getElementById('exercise-content'),
        exerciseCard: document.getElementById('exercise-card'),
        pdfCard: document.getElementById('pdf-card'),
        pdfViewer: document.getElementById('pdf-viewer'),
        scoreResultCard: document.getElementById('score-result-card'),
        scoringContent: document.getElementById('scoring-content'),
        answerCard: document.getElementById('answer-card'),
        pastPaperIdHidden: document.getElementById('past_paper_id_hidden'),
        questionSelectorContainer: document.getElementById('question-selector-container'),
    };

    const renderer = new ExerciseRenderer(elements);
    const paperSelector = new PastPaperSelector({
        selectYear: elements.selectYear,
        selectSeason: elements.selectSeason,
        selectPeriod: elements.selectPeriod,
        pastPapers: pastPapers
    });

    // --- AI演習モードのカテゴリ同期 ---
    if (elements.categorySelect) {
        elements.categorySelect.addEventListener('change', () => {
            ui.syncSubcategories(elements.categorySelect, elements.subcategorySelect, categories, { defaultLabel, noSelectionLabel });
        });
        ui.syncSubcategories(elements.categorySelect, elements.subcategorySelect, categories, { defaultLabel, noSelectionLabel });
    }

    // --- 過去問モードの読み込み ---
    if (elements.btnLoadPaper && elements.selectPeriod) {
        elements.btnLoadPaper.addEventListener('click', async () => {
            const pdfId = elements.selectPeriod.value;
            if (!pdfId) return alert('試験問題を選択してください');

            try {
                ui.startLoading('paper');
                const cat = elements.categorySelect ? elements.categorySelect.value : '';
                await trivia.fetchTrivia(cat);
                trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

                // 1. PDFを表示 (PDF.jsを使用)し、描画完了を待つ
                await renderer.renderPdf(`/exercise/pdf/${pdfId}`);
                elements.pdfCard.classList.remove('hidden');

                // 2. 問題データと解答済み問題を取得
                const res = await api.get(`/exercise/questions/${pdfId}`);

                // 3. 自動的に全問をタブ形式で表示
                if (res.status === 'success' && res.questions_data) {
                    const allQuestions = res.questions_data.questions.map(q => q.question_number);

                    renderer.renderQuestionAnswerForm(res.questions_data, allQuestions, pdfId);

                    const title = `過去問演習: ${elements.selectYear.options[elements.selectYear.selectedIndex].text} ${elements.selectSeason.options[elements.selectSeason.selectedIndex].text} ${elements.selectPeriod.options[elements.selectPeriod.selectedIndex].text}`;
                    elements.scoreForm.querySelector('[name="exercise_text"]').value = title;
                    if (elements.pastPaperIdHidden) {
                        elements.pastPaperIdHidden.value = pdfId;
                    }

                    elements.answerCard.classList.remove('hidden');
                    elements.exerciseCard.classList.add('hidden');

                    // 学習ログを記録
                    const recordData = new FormData();
                    const token = document.querySelector('input[name="_token"]')?.value || '';
                    recordData.append('_token', token);
                    recordData.append('past_paper_id', pdfId);
                    recordData.append('exercise_text', title);
                    recordData.append('selected_questions', JSON.stringify(allQuestions));

                    const recordRes = await api.post('/exercise/record-generation', recordData);
                    if (recordRes.log_id) {
                        window.RissApp.currentLogId = recordRes.log_id;
                    }
                } else {
                    alert('問題データの取得に失敗しました');
                }

            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                ui.stopLoading();
                trivia.stop();
            }
        });
    }

    // --- 文字数カウンター ---
    if (elements.answerArea) {
        elements.answerArea.addEventListener('input', () => ui.updateCharacterCounter(elements.answerArea, elements.countBox));
        ui.updateCharacterCounter(elements.answerArea, elements.countBox);
    }

    // --- 問題生成フォーム送信 ---
    if (elements.generateForm) {
        elements.generateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ui.startLoading('generate');
            await trivia.fetchTrivia(elements.categorySelect.value);
            trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

            try {
                const data = await api.post(elements.generateForm.action, new FormData(elements.generateForm));
                renderer.renderExercise(data);
                if (data.log_id) window.RissApp.currentLogId = data.log_id;
            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                ui.stopLoading();
                trivia.stop();
            }
        });
    }

    // --- 採点フォーム送信 ---
    if (elements.scoreForm) {
        elements.scoreForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (elements.answerArea.classList.contains('hidden')) {
                elements.answerArea.value = renderer.getCombinedDynamicAnswers();
            }

            ui.startLoading('score');
            const cat = elements.scoreForm.querySelector('[name="category"]').value;
            await trivia.fetchTrivia(cat);
            trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

            try {
                const formData = new FormData(elements.scoreForm);
                if (window.RissApp.currentLogId) {
                    formData.append('log_id', window.RissApp.currentLogId);
                }
                const data = await api.post(elements.scoreForm.action, formData);
                renderer.renderScore(data);
            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                ui.stopLoading();
                trivia.stop();
            }
        });
    }

    // --- 初期状態の復元 (URLパラメータ等) ---
    const urlParams = new URLSearchParams(window.location.search);
    const autoPdfId = urlParams.get('pdf_id');

    if (autoPdfId && pastPapers) {
        const paper = pastPapers.find(p => p.id == autoPdfId);
        if (paper) {
            paperSelector.setValue(paper.year, paper.season, paper.id);
            elements.btnLoadPaper.click();
        }
    }

    // --- AI生成問題の再挑戦 (サーバー側で提供された情報を記録) ---
    const retakeLogId = urlParams.get('retake_log_id');
    if (retakeLogId && mode === 'ai_generated' && exerciseRaw) {
        // すでに exerciseRaw で描画されているはずだが、
        // この時点で「新しい解答セッション」としてログを先行記録する
        const recordRetake = async () => {
            const recordData = new FormData();
            const token = document.querySelector('input[name="_token"]')?.value || '';
            recordData.append('_token', token);
            recordData.append('exercise_text', exerciseRaw);
            recordData.append('category', window.RissApp.currentCategory || '');
            recordData.append('subcategory', currentSubcategory || '');

            try {
                const recordRes = await api.post('/exercise/record-generation', recordData);
                if (recordRes.log_id) {
                    window.RissApp.currentLogId = recordRes.log_id;
                }
            } catch (e) {
                console.error('Error recording retake:', e);
            }
        };
        recordRetake();
    }

    if (exerciseRaw) {
        renderer.renderExercise({ exerciseText: exerciseRaw, category: window.RissApp.currentCategory, subcategory: currentSubcategory });
    }
    if (scoringRaw) {
        renderer.renderScore({ scoringResult: scoringRaw });
    }
    renderMermaid();
});
