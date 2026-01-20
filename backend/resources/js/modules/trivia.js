import { APP_CONFIG } from './constants.js';

export class TriviaManager {
    constructor(triviaTextElement) {
        this.triviaText = triviaTextElement;
        this.triviaList = [];
        this.currentIndex = 0;
        this.interval = null;
    }

    async fetchTrivia(category) {
        try {
            const params = new URLSearchParams();
            if (category) params.append('category', category);

            const url = `/api/trivia/random-list?${params.toString()}`;
            const response = await fetch(url);
            if (response.ok) {
                this.triviaList = await response.json();
            }
        } catch (e) {
            console.error('Trivia list fetch error:', e);
            this.triviaList = [];
        }
    }

    showNext() {
        if (!this.triviaText) return;

        if (this.triviaList && this.triviaList.length > 0) {
            const item = this.triviaList[this.currentIndex];
            this.triviaText.textContent = item.content || "セキュリティ意識を高めましょう。";
            this.currentIndex = (this.currentIndex + 1) % this.triviaList.length;
        } else {
            this.triviaText.textContent = "セキュリティ豆知識をご案内します。しばらくお待ちください。";
        }
    }

    start(intervalMs = APP_CONFIG.TRIVIA_INTERVAL) {
        this.currentIndex = 0;
        this.showNext();
        if (this.interval) clearInterval(this.interval);
        this.interval = setInterval(() => this.showNext(), intervalMs);
    }

    stop() {
        if (this.interval) clearInterval(this.interval);
        this.interval = null;
    }
}
