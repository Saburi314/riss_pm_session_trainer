<?php

namespace App\Console\Commands;

use App\Models\PastPaper;
use App\Services\VectorStoreService;
use Illuminate\Console\Command;

class VectorStoreSync extends Command
{
    protected $signature = 'vs:sync
                            {--clear : 実行前に OpenAI 上の全ファイルを削除してリセットする}
                            {--clear-texts : searchable_texts フォルダ内の解析済みテキストも削除する}
                            {--force-ocr : すでに解析済みのファイルも再解析する}
                            {--dry-run : 実際にはアップロードせず、対象ファイルを表示のみ}
                            {--limit=1000 : 一度に処理する最大件数}
                            {--year= : 特定の年度のみ処理（例: --year=2025）}
                            {--year-from= : この年度以降を処理（例: --year-from=2020）}
                            {--year-to= : この年度以前を処理（例: --year-to=2023）}
                            {--doc-type= : 特定の資料種別のみ処理（question/answer/commentary）}';

    protected $description = 'PDFの解析（OCR）とOpenAIベクトルストアへの同期を一括で行います';

    public function handle(VectorStoreService $service, \App\Services\PdfAnalysisService $analysisService): int
    {
        $clear = $this->option('clear');
        $clearTexts = $this->option('clear-texts');
        $forceOcr = $this->option('force-ocr');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $year = $this->option('year');
        $yearFrom = $this->option('year-from');
        $yearTo = $this->option('year-to');
        $docType = $this->option('doc-type');

        // 1. クリーンアップ処理
        if ($clear) {
            $this->warn('Clearing all files in OpenAI Vector Store and Storage...');
            $stats = $service->clearAllOpenAIFiles();
            $this->info("Cleared: {$stats['vector_store']} from store, {$stats['storage']} from storage.");

            // 全レコードのステータスを pending にリセット
            PastPaper::query()->update([
                'openai_file_id' => null,
                'vector_store_file_id' => null,
                'index_status' => 'pending',
                'indexed_at' => null
            ]);
            $this->info('Database statuses reset to pending.');
        }

        // 1.5. ローカルの解析済みテキストファイルも削除
        if ($clearTexts) {
            $this->warn('Clearing all searchable text files...');
            $searchableDir = storage_path('app/searchable_texts');
            if (is_dir($searchableDir)) {
                $files = glob($searchableDir . '/*.txt');
                $count = 0;
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $count++;
                    }
                }
                $this->info("Deleted {$count} searchable text file(s).");
            }
        }

        // 2. 同期対象のPDFを取得
        $query = PastPaper::query();
        if (!$clear) {
            $query->whereIn('index_status', ['pending', 'failed', 'cancelled', 'in_progress']);
        }

        // 年度フィルタ
        if ($year) {
            $query->where('year', $year);
        }
        if ($yearFrom) {
            $query->where('year', '>=', $yearFrom);
        }
        if ($yearTo) {
            $query->where('year', '<=', $yearTo);
        }

        // 資料種別フィルタ
        if ($docType) {
            $query->where('doc_type', $docType);
        }

        // 新しい年度から順に処理（降順）
        $query->orderBy('year', 'desc')->orderBy('season', 'desc');

        $pdfFiles = $query->limit($limit)->get();

        if ($pdfFiles->isEmpty()) {
            $this->info('No files to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$pdfFiles->count()} file(s).");

        if ($dryRun) {
            $this->table(['ID', 'Filename', 'Status'], $pdfFiles->map(fn($f) => [$f->id, $f->filename, $f->index_status]));
            return self::SUCCESS;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($pdfFiles as $pdfFile) {
            $this->line("Processing: {$pdfFile->filename}");

            try {
                // 解析（OCR/テキスト抽出）がまだ、または強制指示がある場合
                if (!$pdfFile->getSearchableTextPath() || $forceOcr) {
                    $this->info("  -> Step 1/2: Extracting text (OCR/Direct)...");
                    $analysisService->analyze($pdfFile);
                } else {
                    $this->info("  -> Step 1/2: Searchable text already exists.");
                }

                $this->info("  -> Step 2/2: Syncing to OpenAI...");
                $service->syncFile($pdfFile, $forceOcr);

                $successCount++;
                $this->info("  ✓ Completed successfully");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Process completed: {$successCount} success, {$errorCount} error(s).");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
