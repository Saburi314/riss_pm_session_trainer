<?php

namespace Database\Seeders;

use App\Models\SecurityTrivia;
use Illuminate\Database\Seeder;

class SecurityTriviaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ========================================
            // MGT: マネジメント
            // ========================================
            ['category' => 'MGT', 'content' => 'ISMS (ISO27001) のPDCAのうち、Checkは「内部監査」や「マネジメントレビュー」が該当します。'],
            ['category' => 'MGT', 'content' => 'リスクアセスメントは「リスク特定」「リスク分析」「リスク評価」の3段階で行われます。'],
            ['category' => 'MGT', 'content' => '「残留リスク」とは、リスク対応を行った後に残るリスクのことです。'],
            ['category' => 'MGT', 'content' => 'BCP (事業継続計画) は、災害時などに重要業務を継続・早期復旧するための計画です。'],
            ['category' => 'MGT', 'content' => '情報セキュリティ方針は、組織のトップが宣言する基本方針であり、全従業員への周知が必要です。'],
            ['category' => 'MGT', 'content' => 'リスク対応には「低減」「回避」「転嫁（移転）」「受容（保有）」の4つの選択肢があります。'],
            ['category' => 'MGT', 'content' => 'JIS Q 27001 は、情報セキュリティマネジメントシステム (ISMS) の要求事項を定めた規格です。'],
            ['category' => 'MGT', 'content' => 'プライバシーマーク (Pマーク) は、JIS Q 15001 に基づく個人情報保護体制の認定制度です。'],
            ['category' => 'MGT', 'content' => '情報セキュリティ監査は、「助言型監査」と「保証型監査」の2種類に大別されます。'],
            ['category' => 'MGT', 'content' => 'リスクマトリクスは、発生可能性と影響度の2軸でリスクを分類・評価する手法です。'],
            ['category' => 'MGT', 'content' => '情報セキュリティの3要素 (CIA) は「機密性」「完全性」「可用性」です。'],
            ['category' => 'MGT', 'content' => 'PDCAサイクルのActは「是正処置」「予防処置」の実施と、継続的改善を意味します。'],
            ['category' => 'MGT', 'content' => 'ISMS適合性評価制度では、認証機関が組織のISMSを審査・認証します。'],
            ['category' => 'MGT', 'content' => 'リスク基準は、リスクを評価するための尺度であり、組織が事前に定める必要があります。'],
            ['category' => 'MGT', 'content' => '情報資産台帳は、情報資産を一覧化し、分類・評価・管理者を明記した文書です。'],
            ['category' => 'MGT', 'content' => 'BCPにおけるRTO (目標復旧時間) は、システムを復旧させるまでの目標時間です。'],
            ['category' => 'MGT', 'content' => 'BCPにおけるRPO (目標復旧地点) は、どの時点のデータまで復旧させるかの目標です。'],
            ['category' => 'MGT', 'content' => 'セキュリティポリシーは「基本方針」「対策基準」「実施手順」の3階層で構成されるのが一般的です。'],
            ['category' => 'MGT', 'content' => '経営陣のコミットメントは、ISMSの有効な運用において最も重要な要素の一つです。'],
            ['category' => 'MGT', 'content' => 'リスクオーナーは、特定のリスクに対する説明責任と権限を持つ人物または組織です。'],

            // ========================================
            // SYS: システム開発・設計・テスト
            // ========================================
            ['category' => 'SYS', 'content' => 'セキュリティバイデザインは、企画・設計段階からセキュリティを組み込む考え方です。'],
            ['category' => 'SYS', 'content' => 'WAF (Web Application Firewall) は、SQLインジェクションやXSSなどのWeb層の攻撃を防御します。'],
            ['category' => 'SYS', 'content' => 'バッファオーバーフローは、メモリ領域の上限を超えてデータを書き込む攻撃です。'],
            ['category' => 'SYS', 'content' => 'ファジング (Fuzzing) は、予測不可能なデータを入力して脆弱性を検出するテスト手法です。'],
            ['category' => 'SYS', 'content' => 'DMZにはWebサーバなどの外部公開サーバを配置し、内部DBは別のセグメントに配置します。'],
            ['category' => 'SYS', 'content' => 'SQLインジェクション対策の基本は、プレースホルダ（プリペアドステートメント）の使用です。'],
            ['category' => 'SYS', 'content' => 'XSS対策の基本は、出力時のエスケープ処理とContent-Security-Policyの設定です。'],
            ['category' => 'SYS', 'content' => 'CSRF対策には、トークンを用いた正規リクエストの検証が有効です。'],
            ['category' => 'SYS', 'content' => 'ディレクトリトラバーサルは、パスを操作して非公開ファイルにアクセスする攻撃です。'],
            ['category' => 'SYS', 'content' => 'セキュリティテストには「静的解析 (SAST)」と「動的解析 (DAST)」があります。'],
            ['category' => 'SYS', 'content' => 'コードレビューは、セキュリティの脆弱性を早期に発見するための重要なプロセスです。'],
            ['category' => 'SYS', 'content' => 'OSコマンドインジェクションは、外部入力をOSコマンドに渡す際に発生する脆弱性です。'],
            ['category' => 'SYS', 'content' => 'セッション管理の不備は、セッションハイジャックやセッション固定攻撃の原因になります。'],
            ['category' => 'SYS', 'content' => 'HTTPヘッダインジェクションは、レスポンスヘッダを改ざんする攻撃です。'],
            ['category' => 'SYS', 'content' => '脅威モデリングは、設計段階で脅威を洗い出し、対策を検討する手法です。'],
            ['category' => 'SYS', 'content' => 'STRIDEは、脅威を6つのカテゴリ（なりすまし、改ざん等）で分類するモデルです。'],
            ['category' => 'SYS', 'content' => 'ペネトレーションテストは、実際に攻撃を模倣してシステムの脆弱性を検証するテストです。'],
            ['category' => 'SYS', 'content' => 'IPA「安全なウェブサイトの作り方」は、Web開発者必読のセキュリティガイドラインです。'],
            ['category' => 'SYS', 'content' => 'OWASP Top 10 は、Webアプリケーションの重大なセキュリティリスクを毎年更新しています。'],
            ['category' => 'SYS', 'content' => 'セキュアコーディングの基本は「入力検証」と「出力時のエスケープ」です。'],

            // ========================================
            // OPS: 運用・物理・人的・サプライチェーン
            // ========================================
            ['category' => 'OPS', 'content' => 'SPF/DKIM/DMARCは、送信ドメイン認証技術であり、メールのなりすまし対策です。'],
            ['category' => 'OPS', 'content' => 'ゼロトラストは、境界防御を廃止し、全てのアクセスを常に検証するモデルです。'],
            ['category' => 'OPS', 'content' => 'ランサムウェア対策では、バックアップをオフラインまたは不変で保管することが最重要です。'],
            ['category' => 'OPS', 'content' => 'パスワードのソルトは、ハッシュ化時にランダムデータを加え、レインボーテーブル攻撃を防ぎます。'],
            ['category' => 'OPS', 'content' => '多要素認証 (MFA) は「知識」「所持」「生体」の3要素のうち2つ以上を組み合わせます。'],
            ['category' => 'OPS', 'content' => 'BYOD導入時は、MDM (Mobile Device Management) 等での端末管理が推奨されます。'],
            ['category' => 'OPS', 'content' => 'SIEM (Security Information and Event Management) は、ログを一元管理し相関分析を行います。'],
            ['category' => 'OPS', 'content' => 'IDS (侵入検知) は検知のみ、IPS (侵入防止) は検知に加えて遮断も行います。'],
            ['category' => 'OPS', 'content' => 'ファイアウォールのパケットフィルタリングは、IPアドレスやポート番号で通信を制御します。'],
            ['category' => 'OPS', 'content' => 'サプライチェーン攻撃は、取引先や子会社などセキュリティの甘い組織を踏み台にします。'],
            ['category' => 'OPS', 'content' => 'ソーシャルエンジニアリングは、人間の心理的な隙を突いて情報を盗む手法です。'],
            ['category' => 'OPS', 'content' => '最小権限の原則は、業務に必要な最低限の権限のみを付与するセキュリティ原則です。'],
            ['category' => 'OPS', 'content' => '職務分掌は、重要な業務を複数人で分担し、不正の機会を減らすための仕組みです。'],
            ['category' => 'OPS', 'content' => 'クリアデスクは、離席時に機密情報を放置しないルールです。'],
            ['category' => 'OPS', 'content' => 'クリアスクリーンは、離席時に画面をロックするルールです。'],
            ['category' => 'OPS', 'content' => 'USBメモリの利用制限は、情報漏えい対策の基本的な運用ルールの一つです。'],
            ['category' => 'OPS', 'content' => 'パッチ管理は、公開された脆弱性を修正するため、定期的にアップデートを適用する運用です。'],
            ['category' => 'OPS', 'content' => 'アクセスログは、不正アクセスの検知やインシデント発生後の調査に不可欠です。'],
            ['category' => 'OPS', 'content' => '入退室管理には、ICカード認証、生体認証、監視カメラなどが用いられます。'],
            ['category' => 'OPS', 'content' => 'データの消去は、単なる削除ではなく、上書きや物理破壊で確実に行う必要があります。'],

            // ========================================
            // INC: インシデント対応
            // ========================================
            ['category' => 'INC', 'content' => 'CSIRT (Computer Security Incident Response Team) は、インシデント対応を行う専門チームです。'],
            ['category' => 'INC', 'content' => 'フォレンジックは、インシデント発生時にログやメモリダンプなどの証拠を保全・分析する技術です。'],
            ['category' => 'INC', 'content' => 'インシデント対応は「検知→初動対応→封じ込め→根絶→復旧→事後対応」の流れが一般的です。'],
            ['category' => 'INC', 'content' => 'SOC (Security Operation Center) は、24時間365日体制でセキュリティ監視を行います。'],
            ['category' => 'INC', 'content' => 'J-CRAT (Cyber Rescue and Advice Team) は、標的型攻撃の被害低減を支援するIPAの組織です。'],
            ['category' => 'INC', 'content' => 'インシデントのトリアージは、優先度を判断し、リソースを効率的に配分するプロセスです。'],
            ['category' => 'INC', 'content' => '証拠保全では、ハッシュ値を記録し、データの完全性を証明できるようにします。'],
            ['category' => 'INC', 'content' => 'チェーンオブカストディは、証拠の取り扱い者を記録し、改ざんがないことを証明する仕組みです。'],
            ['category' => 'INC', 'content' => 'インシデント対応計画は、事前に策定し、定期的な訓練で有効性を検証する必要があります。'],
            ['category' => 'INC', 'content' => '封じ込めフェーズでは、被害拡大を防ぐため、感染端末のネットワーク隔離などを行います。'],
            ['category' => 'INC', 'content' => '根絶フェーズでは、マルウェアの完全除去や、悪用された脆弱性の修正を行います。'],
            ['category' => 'INC', 'content' => '事後対応では、再発防止策の策定と、関係者への報告・情報共有を行います。'],
            ['category' => 'INC', 'content' => 'インシデント報告書は、発生事象・時系列・原因・対応・改善策を記録した文書です。'],
            ['category' => 'INC', 'content' => 'JPCERT/CCは、日本におけるコンピュータセキュリティインシデントの調整機関です。'],
            ['category' => 'INC', 'content' => 'マルウェア感染時は、電源を切らずにメモリダンプを取得することが推奨される場合があります。'],
            ['category' => 'INC', 'content' => 'IOC (Indicator of Compromise) は、侵害の痕跡を示す情報（IPアドレス、ハッシュ値等）です。'],
            ['category' => 'INC', 'content' => 'TTPは、攻撃者の戦術 (Tactics)・技術 (Techniques)・手順 (Procedures) を指します。'],
            ['category' => 'INC', 'content' => 'MITRE ATT&CKは、攻撃者の行動パターンを体系化したナレッジベースです。'],
            ['category' => 'INC', 'content' => 'サイバーキルチェーンは、攻撃の段階を7フェーズに分けて分析するフレームワークです。'],
            ['category' => 'INC', 'content' => 'インシデント情報の共有には、STIX/TAXIIなどの標準フォーマットが利用されます。'],
        ];

        foreach ($data as $item) {
            SecurityTrivia::create($item);
        }
    }
}
