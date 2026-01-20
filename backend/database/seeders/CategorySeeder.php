<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\PromptService;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
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

        foreach ($categories as $catCode => $catData) {
            $category = Category::firstOrCreate(
                ['code' => $catCode],
                ['name' => $catData['category']]
            );

            foreach ($catData['subcategories'] as $subCode => $subName) {
                Subcategory::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'code' => $subCode
                    ],
                    ['name' => $subName]
                );
            }
        }
    }
}
