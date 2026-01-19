<?php

namespace App\Console\Commands;

use App\Models\PdfFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VectorStoreSync extends Command
{
    protected $signature = 'vs:sync
                            {--dry-run : 実際にはアップロードせず、対象ファイルを表示のみ}
                            {--limit=10 : 一度に処理する最大件数}
                            {--retry : failed や cancelled のファイルも対象にする}';

    protected $description = 'Sync pending/failed PDFs to OpenAI Vector Store';

    private string $apiKey;
    private string $vectorStoreId;

    public function handle(): int
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->vectorStoreId = (string) config('services.openai.vector_store_id');

        if (!$this->apiKey) {
            $this->error('OPENAI_API_KEY is not set.');
            return self::FAILURE;
        }

        if (!$this->vectorStoreId) {
            $this->error('OPENAI_VECTOR_STORE_ID is not set.');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $retry = $this->option('retry');
        $limit = (int) $this->option('limit');

        // 同期対象のPDFを取得
        $query = PdfFile::query();
        if ($retry) {
            $query->whereIn('index_status', ['pending', 'failed', 'cancelled']);
        } else {
            $query->where('index_status', 'pending');
        }

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
                $this->syncFile($pdfFile);
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

    private function syncFile(PdfFile $pdfFile): void
    {
        try {
            $openaiFileId = $this->uploadToFilesApi($pdfFile);
            $this->line("  → Files API: {$openaiFileId}");

            $result = $this->addToVectorStore($openaiFileId, $pdfFile);
            $this->line("  → Vector Store: {$result['id']} (status: {$result['status']})");

            $pdfFile->update([
                'openai_file_id' => $openaiFileId,
                'vector_store_file_id' => $result['id'],
                'index_status' => $result['status'],
                'indexed_at' => $result['status'] === 'completed' ? now() : null,
                'error_message' => $result['error_message'],
            ]);

        } catch (\Exception $e) {
            $pdfFile->update([
                'index_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function uploadToFilesApi(PdfFile $pdfFile): string
    {
        $filePath = $pdfFile->getFullStoragePath();

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $response = Http::timeout(120)
            ->withToken($this->apiKey)
            ->attach('file', file_get_contents($filePath), $pdfFile->filename)
            ->post('https://api.openai.com/v1/files', [
                'purpose' => 'assistants',
            ]);

        if (!$response->ok()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Files API error: {$error}");
        }

        return $response->json('id');
    }

    private function addToVectorStore(string $openaiFileId, PdfFile $pdfFile): array
    {
        $attributes = [
            'year' => (string) $pdfFile->year,
            'season' => $pdfFile->season,
            'exam_period' => $pdfFile->exam_period,
            'doc_type' => $pdfFile->doc_type,
        ];

        $response = Http::timeout(60)
            ->withToken($this->apiKey)
            ->post("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files", [
                'file_id' => $openaiFileId,
                'attributes' => $attributes,
            ]);

        if (!$response->ok()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Vector Store API error: {$error}");
        }

        $vectorStoreFileId = $response->json('id');
        $status = $response->json('status');

        if ($status !== 'completed') {
            return $this->waitForCompletion($vectorStoreFileId);
        }

        return [
            'id' => $vectorStoreFileId,
            'status' => $status,
            'error_message' => null,
        ];
    }

    private function waitForCompletion(string $vectorStoreFileId): array
    {
        $maxAttempts = 15;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            sleep(2);

            $response = Http::timeout(30)
                ->withToken($this->apiKey)
                ->get("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files/{$vectorStoreFileId}");

            if (!$response->ok()) {
                continue;
            }

            $status = $response->json('status');
            $this->line("  → Waiting... (status: {$status})");

            if ($status === 'completed' || $status === 'failed' || $status === 'cancelled') {
                return [
                    'id' => $vectorStoreFileId,
                    'status' => $status,
                    'error_message' => $response->json('last_error.message'),
                ];
            }
        }

        return [
            'id' => $vectorStoreFileId,
            'status' => 'in_progress',
            'error_message' => 'Timeout waiting for Vector Store processing',
        ];
    }
}
