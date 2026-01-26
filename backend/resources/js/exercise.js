import { UIManager } from './modules/ui.js';
import { TriviaManager } from './modules/trivia.js';
import { parseMarkdown, renderMermaid } from './modules/renderer.js';
import * as api from './modules/api.js';
import { APP_CONFIG } from './modules/constants.js';

document.addEventListener('DOMContentLoaded', () => {
    const { categories, currentSubcategory, defaultLabel, noSelectionLabel, exerciseRaw, scoringRaw } = window.RissApp || {};

    const ui = new UIManager();
    const trivia = new TriviaManager(document.getElementById('trivia-text'));

    const categorySelect = document.getElementById('category');
    const subcategorySelect = document.getElementById('subcategory');
    const generateForm = document.getElementById('form-generate');
    const scoreForm = document.getElementById('form-score');
    const answerArea = document.getElementById('user_answer');
    const countBox = document.getElementById('segment-counters');

    const urlParams = new URLSearchParams(window.location.search);
    if (categorySelect) {
        // 先にURLパラメータをチェック
        const paramCategory = urlParams.get(APP_CONFIG.PARAMS.CATEGORY);
        const paramSubcategory = urlParams.get(APP_CONFIG.PARAMS.SUBCATEGORY);

        if (paramCategory !== null) {
            categorySelect.value = paramCategory;
        }

        categorySelect.addEventListener('change', () => {
            ui.syncSubcategories(categorySelect, subcategorySelect, categories, { defaultLabel, noSelectionLabel });
        });
        ui.syncSubcategories(categorySelect, subcategorySelect, categories, { defaultLabel, noSelectionLabel });

        if (paramSubcategory !== null) {
            subcategorySelect.value = paramSubcategory;
            if (paramSubcategory !== "") subcategorySelect.disabled = false;
        } else if (currentSubcategory) {
            subcategorySelect.value = currentSubcategory;
            subcategorySelect.disabled = false;
        }
    }

    if (answerArea) {
        answerArea.addEventListener('input', () => ui.updateCharacterCounter(answerArea, countBox));
        ui.updateCharacterCounter(answerArea, countBox);
    }

    if (generateForm) {
        generateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ui.startLoading('generate');
            await trivia.fetchTrivia(categorySelect.value);
            trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

            try {
                const data = await api.post(generateForm.action, new FormData(generateForm));
                renderExerciseResult(data);
            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                ui.stopLoading();
                trivia.stop();
            }
        });
    }

    if (scoreForm) {
        scoreForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ui.startLoading('score');
            await trivia.fetchTrivia(categorySelect.value);
            trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

            try {
                const formData = new FormData(scoreForm);
                if (window.RissApp.currentLogId) {
                    formData.append('log_id', window.RissApp.currentLogId);
                }
                const data = await api.post(scoreForm.action, formData);
                renderScoreResult(data);
            } catch (error) {
                console.error(error);
                alert('エラーが発生しました: ' + error.message);
            } finally {
                ui.stopLoading();
                trivia.stop();
            }
        });
    }

    function renderExerciseResult(data) {
        const contentDiv = document.getElementById('exercise-content');
        if (contentDiv) {
            contentDiv.innerHTML = parseMarkdown(data.exerciseText);
            document.getElementById('exercise-card').classList.remove('hidden');
        }

        if (data.log_id) {
            window.RissApp.currentLogId = data.log_id;
        }

        const form = document.getElementById('form-score');
        if (form) {
            form.querySelector('[name="category"]').value = data.category || '';
            form.querySelector('[name="subcategory"]').value = data.subcategory || '';
            form.querySelector('[name="exercise_text"]').value = data.exerciseText || '';
            document.getElementById('answer-card').classList.remove('hidden');
        }

        const scoreResultCard = document.getElementById('score-result-card');
        if (scoreResultCard) scoreResultCard.classList.add('hidden');

        renderMermaid();
    }

    function renderScoreResult(data) {
        const scoreCard = document.getElementById('score-result-card');
        const scoreContainer = document.getElementById('scoring-content');

        if (!scoreCard || !scoreContainer) return;

        const match = (data.scoringResult || '').match(/点数：(\d+)/);
        const scoreValue = match ? parseInt(match[1]) : 0;

        let scoreColor = APP_CONFIG.SCORING.COLORS.BRONZE;
        if (scoreValue >= APP_CONFIG.SCORING.THRESHOLDS.GOLD) {
            scoreColor = APP_CONFIG.SCORING.COLORS.GOLD;
        } else if (scoreValue >= APP_CONFIG.SCORING.THRESHOLDS.SILVER) {
            scoreColor = APP_CONFIG.SCORING.COLORS.SILVER;
        }

        scoreCard.classList.remove('hidden');

        const badge = scoreCard.querySelector('.score-badge');
        if (badge) {
            badge.style.background = scoreColor;
            badge.style.boxShadow = `0 10px 25px ${scoreColor}66`;
            badge.textContent = scoreValue;
        }

        const label = scoreCard.querySelector('.score-label');
        if (label) label.style.color = scoreColor;

        scoreContainer.innerHTML = parseMarkdown(data.scoringResult);
        scoreCard.scrollIntoView({ behavior: 'smooth' });
    }

    if (exerciseRaw) {
        renderExerciseResult({ exerciseText: exerciseRaw, category: window.RissApp.currentCategory, subcategory: currentSubcategory });
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get(APP_CONFIG.PARAMS.RETAKE) === '1') {
            const retakeExercise = sessionStorage.getItem(APP_CONFIG.SESSION_KEYS.RETAKE_EXERCISE);
            const retakeCategory = sessionStorage.getItem(APP_CONFIG.SESSION_KEYS.RETAKE_CATEGORY);
            const retakeSubcategory = sessionStorage.getItem(APP_CONFIG.SESSION_KEYS.RETAKE_SUBCATEGORY);

            if (retakeExercise) {
                renderExerciseResult({
                    exerciseText: retakeExercise,
                    category: retakeCategory,
                    subcategory: retakeSubcategory
                });

                (async () => {
                    try {
                        const formData = new FormData();
                        const token = document.querySelector('input[name="_token"]')?.value || '';
                        formData.append('_token', token);
                        formData.append('category', retakeCategory || '');
                        formData.append('subcategory', retakeSubcategory || '');
                        formData.append('exercise_text', retakeExercise);

                        const res = await api.post('/exercise/record-generation', formData);
                        if (res.log_id) {
                            window.RissApp.currentLogId = res.log_id;
                        }
                    } catch (e) {
                        console.error('Failed to log retake attempt', e);
                    }
                })();

                if (categorySelect && retakeCategory) {
                    categorySelect.value = retakeCategory;
                    ui.syncSubcategories(categorySelect, subcategorySelect, categories, { defaultLabel, noSelectionLabel });
                    if (subcategorySelect && retakeSubcategory) {
                        subcategorySelect.value = retakeSubcategory;
                        subcategorySelect.disabled = false;
                    }
                }
            }
        }
    }

    if (scoringRaw) {
        renderScoreResult({ scoringResult: scoringRaw });
    }
    renderMermaid();
});
