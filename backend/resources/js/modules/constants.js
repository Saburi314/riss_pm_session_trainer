/**
 * Application Constants
 */
export const APP_CONFIG = {
    // 豆知識の切り替え間隔 (ms)
    TRIVIA_INTERVAL: 15000,

    // 経過秒タイマーの更新間隔 (ms)
    TIMER_INTERVAL: 1000,

    // Mermaid図解のレンダリング遅延 (ms)
    MERMAID_RENDER_DELAY: 150,

    // プログレスバーの更新間隔 (ms)
    PROGRESS_UPDATE_INTERVAL: 400,

    // プログレスの段階設定
    PROGRESS_STAGES: {
        SEARCH: {
            max: 20, speed: 4.0,
            text: { generate: "過去問ナレッジベースを検索中...", score: "提出された解答を構文解析中...", paper: "過去問データベースを照合中..." }
        },
        ANALYZE: {
            max: 50, speed: 3.5,
            text: { generate: "試験シラバスに基づき問題構成を分析中...", score: "採点基準との適合性を照合中...", paper: "試験問題の構成を確認中..." }
        },
        DRAFT: {
            max: 80, speed: 2.0,
            text: { generate: "AIによる推論および演習シナリオの構築中...", score: "専門的知見に基づく講評を策定中...", paper: "解答フォームを構築中..." }
        },
        FINALIZE: {
            max: 99, speed: 0.8,
            text: { generate: "出力形式の最終調整および生成中...", score: "フィードバック結果を整理中...", paper: "表示の最終調整中..." }
        }
    },

    // 採点設定
    SCORING: {
        THRESHOLDS: {
            GOLD: 80,
            SILVER: 60
        },
        COLORS: {
            GOLD: '#48bb78',   // green-500
            SILVER: '#ecc94b', // yellow-500
            BRONZE: '#f6ad55'  // orange-400
        }
    },

    // セッションストレージのキー
    SESSION_KEYS: {
        RETAKE_EXERCISE: 'riss_retake_exercise',
        RETAKE_CATEGORY: 'riss_retake_category',
        RETAKE_SUBCATEGORY: 'riss_retake_subcategory'
    },

    // URLパラメータ
    PARAMS: {
        RETAKE: 'retake',
        CATEGORY: 'category',
        SUBCATEGORY: 'subcategory'
    }
};
