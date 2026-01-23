<?php

namespace App\Console\Commands;

use App\Models\PdfFile;
use App\Services\VectorStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VectorStoreReset extends Command
{
    protected $signature = 'vs:reset {--force : 確認なしで実行}';
    protected $description = 'ベクターストア内の全ファイルを削除し、DBの同期ステータスをリセットします';

    public function handle(VectorStoreService $service): int
    {
        if (!$this->option('force') && !$this->confirm('ベクターストアの内容をすべて削除し、DBのステータスをリセットしますか？')) {
            $this->warn('キャンセルされました。');
            return self::FAILURE;
        }

        $this->info('1. OpenAI 側の全データをクリーンアップ中 (Vector Store & Files API)...');
        try {
            $stats = $service->clearAllOpenAIFiles();
            $this->info("   ✓ ベクターストアからの解除: {$stats['vector_store']} 件");
            $this->info("   ✓ ストレージからの本体削除: {$stats['storage']} 件");
        } catch (\Exception $e) {
            $this->error('   ✗ エラー: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('2. ローカルデータベースのステータスをリセット中...');
        try {
            PdfFile::query()->update([
                'openai_file_id' => null,
                'vector_store_file_id' => null,
                'index_status' => 'pending',
                'indexed_at' => null,
                'error_message' => null,
            ]);
            $this->info('   ✓ ステータスを pending にリセットしました。');
        } catch (\Exception $e) {
            $this->error('   ✗ データベースの更新に失敗しました: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('リセットが完了しました。php artisan vs:sync で同期をやり直してください。');

        return self::SUCCESS;
    }
}
