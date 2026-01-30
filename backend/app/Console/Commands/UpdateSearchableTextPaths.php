<?php

namespace App\Console\Commands;

use App\Models\PdfFile;
use Illuminate\Console\Command;

class UpdateSearchableTextPaths extends Command
{
    protected $signature = 'pdf:update-searchable-paths';
    protected $description = '既存のsearchable_textsファイルのパスをDBに登録する';

    public function handle(): int
    {
        $this->info('Updating searchable_text_path for existing files...');

        $searchableDir = storage_path('app/searchable_texts');

        if (!is_dir($searchableDir)) {
            $this->error("Directory not found: {$searchableDir}");
            return self::FAILURE;
        }

        $files = glob($searchableDir . '/*.txt');
        $this->info("Found " . count($files) . " text files.");

        $updated = 0;
        $skipped = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath, '.txt'); // Remove .txt extension

            $pdfFile = PdfFile::where('filename', $filename)->first();

            if (!$pdfFile) {
                $this->warn("  PDF record not found for: {$filename}");
                $skipped++;
                continue;
            }

            // 既にパスが登録されている場合はスキップ
            if ($pdfFile->searchable_text_path) {
                $skipped++;
                continue;
            }

            $relativePath = 'searchable_texts/' . basename($filePath);
            $pdfFile->update(['searchable_text_path' => $relativePath]);

            $this->line("  ✓ Updated: {$filename}");
            $updated++;
        }

        $this->newLine();
        $this->info("Process completed:");
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
