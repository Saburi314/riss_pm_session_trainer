<?php

namespace App\Services;

use App\Prompts\RissPrompts;

class PromptService
{
    /**
     * RISS 試験範囲に基づく階層型カテゴリ定義
     */
    public const CATEGORIES = [
        'MGT' => [
            'category' => '情報セキュリティマネジメントの推進または支援に関すること',
            'subcategories' => [
                'policy' => '情報セキュリティ方針の策定',
                'risk_assessment' => '情報セキュリティリスクアセスメント',
                'risk_response' => '情報セキュリティリスク対応',
                'regulations' => '情報セキュリティ諸規定の策定',
                'audit' => '情報セキュリティ監査',
                'trends' => '情報セキュリティに関する動向・事例の収集と分析',
                'communication' => '関係者とのコミュニケーション',
            ],
        ],
        'SYS' => [
            'category' => '情報システムの企画・設計・開発・運用でのセキュリティ確保の推進又は支援に関する事',
            'subcategories' => [
                'planning' => '企画・要件定義（セキュリティの観点）',
                'deployment' => '製品・サービスのセキュアな導入',
                'architecture' => 'アーキテクチャの設計（セキュリティの観点）',
                'implementation' => 'セキュリティ機能の設計・実装',
                'programming' => 'セキュアプログラミング',
                'testing' => 'セキュリティテスト',
                'maintenance' => '運用・保守（セキュリティの観点）',
                'dev_env' => '開発環境のセキュリティ確保',
            ],
        ],
        'OPS' => [
            'category' => '情報及び情報システムの利用におけるセキュリティ対策の適用の推進又は支援に関すること',
            'subcategories' => [
                'crypto' => '暗号利用及び鍵管理',
                'malware' => 'マルウェア対策',
                'backup' => 'バックアップ',
                'monitoring' => 'セキュリティ監視並びにログの取得及び分析',
                'network' => 'ネットワークおよび機器のセキュリティ管理',
                'vulnerability' => '脆弱性への対応',
                'physical' => '物理的セキュリティ管理',
                'account' => 'アカウント管理及びアクセス管理',
                'human' => '人的管理',
                'supply_chain' => 'サプライチェーンの情報セキュリティの推進',
                'compliance' => 'コンプライアンス管理',
            ],
        ],
        'INC' => [
            'category' => '情報セキュリティインシデント管理の推進又は支援に関すること',
            'subcategories' => [
                'org' => '情報セキュリティインシデントの管理体制の構築',
                'assessment' => '情報セキュリティ事象の評価',
                'response' => '情報セキュリティインシデントへの対応',
                'evidence' => '証拠の収集及び分析',
            ],
        ],
    ];

    public function buildGeneratePrompt(?string $category, ?string $subcategory): string
    {
        $context = $this->getCategoryDisplayNames($category, $subcategory);
        return RissPrompts::getGeneratePrompt($context);
    }

    public function buildScorePrompt(string $exerciseText, string $userAnswer, ?string $category, ?string $subcategory): string
    {
        $context = $this->getCategoryDisplayNames($category, $subcategory);
        return RissPrompts::getScorePrompt($exerciseText, $userAnswer, $context);
    }

    private function getCategoryDisplayNames(?string $categoryKey, ?string $subcategoryKey): array
    {
        $categoryLabel = '全般（ランダム）';
        $subcategoryLabel = '全般（ランダム）';

        if ($categoryKey && isset(self::CATEGORIES[$categoryKey])) {
            $categoryLabel = self::CATEGORIES[$categoryKey]['category'];
            if ($subcategoryKey && isset(self::CATEGORIES[$categoryKey]['subcategories'][$subcategoryKey])) {
                $subcategoryLabel = self::CATEGORIES[$categoryKey]['subcategories'][$subcategoryKey];
            }
        }

        return ['category' => $categoryLabel, 'subcategory' => $subcategoryLabel];
    }
}
