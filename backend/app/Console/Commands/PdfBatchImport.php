<?php

namespace App\Console\Commands;

use App\Models\PdfFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PdfBatchImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:batch-import 
                            {directory : PDFファイルが格納されているディレクトリのパス}
                            {--disk=local : 登録先のディスク}
                            {--force : 確認なしで登録を実行する}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan a directory and auto-register PDFs based on filename patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dir = $this->argument('directory');
        $disk = $this->option('disk');

        if (!is_dir($dir)) {
            $this->error("Directory not found: {$dir}");
            return self::FAILURE;
        }

        $files = File::files($dir);
        $pdfFiles = array_filter($files, fn($f) => str_ends_with(strtolower($f->getFilename()), '.pdf'));

        if (empty($pdfFiles)) {
            $this->warn("No PDF files found in {$dir}");
            return self::SUCCESS;
        }

        $this->info("Found " . count($pdfFiles) . " PDF files. Parsing...");

        $results = [];
        $errors = [];

        foreach ($pdfFiles as $file) {
            $metadata = $this->parseFilename($file->getFilename());

            if (!$metadata) {
                $errors[] = [$file->getFilename(), "Pattern mismatch"];
                continue;
            }

            $results[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'metadata' => $metadata
            ];
        }

        // 結果のプレビュー表示
        $this->table(
            ['Filename', 'Year', 'Season', 'Period', 'Type'],
            array_map(fn($r) => [
                $r['filename'],
                $r['metadata']['year'],
                $r['metadata']['season'],
                $r['metadata']['exam_period'],
                $r['metadata']['doc_type'],
            ], $results)
        );

        if (!empty($errors)) {
            $this->warn("The following files could not be parsed:");
            $this->table(['Filename', 'Reason'], $errors);
        }

        if (empty($results)) {
            $this->info("No new or valid files to register.");
            return self::SUCCESS;
        }

        $this->info("Registering " . count($results) . " new files...");

        $successCount = 0;
        $skippedCount = 0;

        foreach ($results as $item) {
            try {
                $year = $item['metadata']['year'];
                $season = $item['metadata']['season'];
                $targetSubDir = "private/pdfs/{$year}/{$season}";
                $targetDir = storage_path("app/{$targetSubDir}");

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $targetPath = "{$targetSubDir}/" . $item['filename'];
                $absoluteTargetPath = storage_path("app/{$targetPath}");

                // 同一ファイルでない場合のみコピー
                if ($item['path'] !== $absoluteTargetPath) {
                    copy($item['path'], $absoluteTargetPath);
                }

                // 既に登録済みかチェック（storage_path で判断）
                $existing = PdfFile::where('storage_path', $targetPath)->first();
                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                PdfFile::create([
                    'filename' => $item['filename'],
                    'storage_disk' => $disk,
                    'storage_path' => $targetPath,
                    'size' => filesize($absoluteTargetPath),
                    'year' => (int) $year,
                    'season' => $season,
                    'exam_period' => $item['metadata']['exam_period'],
                    'doc_type' => $item['metadata']['doc_type'],
                    'index_status' => 'pending',
                ]);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("Failed to register {$item['filename']}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Import completed:");
        $this->info("  - New registered: {$successCount}");
        $this->info("  - Already exists (skipped): {$skippedCount}");

        if ($successCount > 0) {
            $this->info("Run `php artisan vs:sync` to sync new files with OpenAI.");
        }

        return self::SUCCESS;
    }

    /**
     * Filename pattern: 2024r06h_sc_pm_qs.pdf
     */
    private function parseFilename(string $filename): ?array
    {
        // Regex pattern:
        // 1: \d{4} - 西暦
        // 2: [hao] - 時期 (h=spring, a=autumn, o=autumn/2020 special)
        // 3: (am2|pm[12]?) - 区分
        // 4: ([a-z0-9]+) - 種別 
        $pattern = '/^(\d{4}).*?([hao]).*?_(am2|pm[12]?)_([a-z0-9]+)\.pdf$/i';

        if (!preg_match($pattern, $filename, $matches)) {
            return null;
        }

        $year = $matches[1];
        $seasonCode = strtolower($matches[2]);
        $period = strtolower($matches[3]);
        $typeCode = strtolower($matches[4]);

        $season = ($seasonCode === 'h') ? 'spring' : 'autumn';

        $docType = match ($typeCode) {
            'qs' => 'question',
            'ans' => 'answer',
            'cmnt' => 'commentary',
            default => null
        };

        if ($docType === null) {
            return null;
        }

        return [
            'year' => $year,
            'season' => $season,
            'exam_period' => $period,
            'doc_type' => $docType,
        ];
    }
}
