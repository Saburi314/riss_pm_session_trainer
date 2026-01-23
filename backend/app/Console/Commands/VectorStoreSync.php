<?php

namespace App\Console\Commands;

use App\Models\PdfFile;
use App\Services\VectorStoreService;
use Illuminate\Console\Command;

class VectorStoreSync extends Command
{
    protected $signature = 'vs:sync
                            {--dry-run : 実際にはアップロードせず、対象ファイルを表示のみ}
                            {--limit=1000 : 一度に処理する最大件数}';

    protected $description = '未同期・失敗したPDFをOpenAIのベクトルストアへ同期します';

    public function handle(VectorStoreService $service): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        // 同期対象のPDFを取得
        $query = PdfFile::query();
        $query->whereIn('index_status', ['pending', 'failed', 'cancelled', 'in_progress']);

        $pendingFiles = $query->limit($limit)->get();

        if ($pendingFiles->isEmpty()) {
            $this->info('No pending files to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingFiles->count()} pending file(s).");

        if ($dryRun) {
            $this->table(
                ['ID', 'Filename', 'Year', 'Season', 'Period', 'Type'],
                $pendingFiles->map(fn($f) => [
                    $f->id,
                    $f->filename,
                    $f->year,
                    $f->season,
                    $f->exam_period,
                    $f->doc_type,
                ])
            );
            $this->warn('Dry run mode - no changes made.');
            return self::SUCCESS;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($pendingFiles as $pdfFile) {
            $this->line("Processing: {$pdfFile->filename}");

            try {
                $service->syncFile($pdfFile);
                $successCount++;
                $this->info("  ✓ Synced successfully");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Sync completed: {$successCount} success, {$errorCount} error(s).");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
