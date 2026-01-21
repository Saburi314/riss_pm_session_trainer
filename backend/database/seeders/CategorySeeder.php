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
            'MANAGEMENT' => [
                'category' => '情報セキュリティマネジメントの推進または支援に関すること',
                'subcategories' => [
                    'POLICY' => '情報セキュリティ方針の策定',
                    'RISK_ASSESSMENT' => '情報セキュリティリスクアセスメント',
                    'RISK_RESPONSE' => '情報セキュリティリスク対応',
                    'REGULATIONS' => '情報セキュリティ諸規定の策定',
                    'AUDIT' => '情報セキュリティ監査',
                    'TRENDS' => '情報セキュリティに関する動向・事例の収集と分析',
                    'COMMUNICATION' => '関係者とのコミュニケーション',
                ],
            ],
            'SYSTEM' => [
                'category' => '情報システムの企画・設計・開発・運用でのセキュリティ確保の推進又は支援に関する事',
                'subcategories' => [
                    'PLANNING' => '企画・要件定義（セキュリティの観点）',
                    'DEPLOYMENT' => '製品・サービスのセキュアな導入',
                    'ARCHITECTURE' => 'アーキテクチャの設計（セキュリティの観点）',
                    'IMPLEMENTATION' => 'セキュリティ機能の設計・実装',
                    'PROGRAMMING' => 'セキュアプログラミング',
                    'TESTING' => 'セキュリティテスト',
                    'MAINTENANCE' => '運用・保守（セキュリティの観点）',
                    'DEV_ENV' => '開発環境のセキュリティ確保',
                ],
            ],
            'OPERATION' => [
                'category' => '情報及び情報システムの利用におけるセキュリティ対策の適用の推進又は支援に関すること',
                'subcategories' => [
                    'CRYPTO' => '暗号利用及び鍵管理',
                    'MALWARE' => 'マルウェア対策',
                    'BACKUP' => 'バックアップ',
                    'MONITORING' => 'セキュリティ監視並びにログの取得及び分析',
                    'NETWORK' => 'ネットワークおよび機器のセキュリティ管理',
                    'VULNERABILITY' => '脆弱性への対応',
                    'PHYSICAL' => '物理的セキュリティ管理',
                    'ACCOUNT' => 'アカウント管理及びアクセス管理',
                    'HUMAN' => '人的管理',
                    'SUPPLY_CHAIN' => 'サプライチェーンの情報セキュリティの推進',
                    'COMPLIANCE' => 'コンプライアンス管理',
                ],
            ],
            'INCIDENT' => [
                'category' => '情報セキュリティインシデント管理の推進又は支援に関すること',
                'subcategories' => [
                    'ORG' => '情報セキュリティインシデントの管理体制の構築',
                    'ASSESSMENT' => '情報セキュリティ事象の評価',
                    'RESPONSE' => '情報セキュリティインシデントへの対応',
                    'EVIDENCE' => '証拠の収集及び分析',
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
