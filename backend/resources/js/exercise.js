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

    // Initialization
    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            ui.syncSubcategories(categorySelect, subcategorySelect, categories, { defaultLabel, noSelectionLabel });
        });
        ui.syncSubcategories(categorySelect, subcategorySelect, categories, { defaultLabel, noSelectionLabel });
        if (currentSubcategory) subcategorySelect.value = currentSubcategory;
    }

    if (answerArea) {
        answerArea.addEventListener('input', () => ui.updateCharacterCounter(answerArea, countBox));
        ui.updateCharacterCounter(answerArea, countBox);
    }

    // Form Submissions
    if (generateForm) {
        generateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ui.startLoading('generate');
            await trivia.fetchTrivia(categorySelect.value);
            trivia.start(APP_CONFIG.TRIVIA_INTERVAL);

            try {
                const data = await api.postGenerate(generateForm.action, new FormData(generateForm));
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
                const data = await api.postScore(scoreForm.action, new FormData(scoreForm));
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

    // Result Rendering
    function renderExerciseResult(data) {
        const contentDiv = document.getElementById('exercise-content');
        if (contentDiv) {
            contentDiv.innerHTML = parseMarkdown(data.exerciseText);
            document.getElementById('exercise-card').classList.remove('hidden');
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
        let scoreContainer = document.getElementById('scoring-content');
        if (!scoreContainer) {
            const anchor = document.getElementById('score-result-anchor');

            const resultHtml = `
            <div id="score-result-card" class="card score-result-card">
              <div id="score-badge-container"></div>
              <div class="score-label">今回の得点</div>
              <div id="scoring-content" class="markdown-body scoring-content"></div>
            </div>`;

            anchor.insertAdjacentHTML('beforebegin', resultHtml);
            scoreContainer = document.getElementById('scoring-content');
        }

        const match = (data.scoringResult || '').match(/点数：(\d+)/);
        const scoreValue = match ? parseInt(match[1]) : 0;
        const scoreColor = scoreValue >= 80 ? '#48bb78' : (scoreValue >= 60 ? '#ecc94b' : '#f6ad55');

        const scoreCard = scoreContainer.closest('.card');
        scoreCard.id = 'score-result-card';
        scoreCard.classList.remove('hidden');

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

    // Initial Display (if data exists)
    if (exerciseRaw) {
        document.getElementById('exercise-content').innerHTML = parseMarkdown(exerciseRaw);
        document.getElementById('exercise-card').classList.remove('hidden');
        document.getElementById('answer-card').classList.remove('hidden');
    }
    if (scoringRaw) {
        renderScoreResult({ scoringResult: scoringRaw });
    }
    renderMermaid();
});
