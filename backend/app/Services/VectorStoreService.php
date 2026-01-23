<?php

namespace App\Services;

use App\Models\PdfFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VectorStoreService
{
    private string $apiKey;
    private string $vectorStoreId;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->vectorStoreId = (string) config('services.openai.vector_store_id');
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
        ];
    }

    /**
     * PDFファイルをOpenAIと同期します。
     * 各ステップごとにDBを更新し、中断しても続きから再開できるようにします。
     */
    public function syncFile(PdfFile $pdfFile): array
    {
        if (!$this->apiKey || !$this->vectorStoreId) {
            throw new \RuntimeException('OpenAI API key or Vector Store ID is not configured.');
        }

        try {
            // ステップ 1: OpenAI Files API へのアップロード (IDがなければ実行)
            if (!$pdfFile->openai_file_id) {
                Log::info("Step 1/3: Uploading to OpenAI Files API: {$pdfFile->filename}");
                $openaiFileId = $this->uploadToFilesApi($pdfFile);
                $pdfFile->update(['openai_file_id' => $openaiFileId]);
            } else {
                Log::info("Step 1/3: Using existing OpenAI File ID: {$pdfFile->openai_file_id}");
            }

            // ステップ 2: ベクターストアへの追加 (IDがない、または前回失敗/キャンセルの場合に実行)
            $unstableStatuses = ['failed', 'cancelled', 'pending', null];
            if (!$pdfFile->vector_store_file_id || in_array($pdfFile->index_status, $unstableStatuses)) {
                Log::info("Step 2/3: Adding to Vector Store: {$pdfFile->openai_file_id}");
                $result = $this->addToVectorStore($pdfFile->openai_file_id, $pdfFile);

                $pdfFile->update([
                    'vector_store_file_id' => $result['id'],
                    'index_status' => $result['status'],
                ]);
            } else {
                Log::info("Step 2/3: Already in Vector Store. ID: {$pdfFile->vector_store_file_id} (Status: {$pdfFile->index_status})");
            }

            // ステップ 3: 完了待機ポーリング (完了するまでループ)
            if ($pdfFile->index_status !== 'completed') {
                Log::info("Step 3/3: Waiting for completion. Current status: {$pdfFile->index_status}");
                $result = $this->waitForCompletion($pdfFile->vector_store_file_id);

                $pdfFile->update([
                    'index_status' => $result['status'],
                    'indexed_at' => $result['status'] === 'completed' ? now() : null,
                    'error_message' => $result['error_message'] ?? null,
                ]);

                return $result;
            }

            return [
                'id' => $pdfFile->vector_store_file_id,
                'status' => 'completed',
                'error_message' => null,
            ];

        } catch (\Exception $e) {
            // OpenAI側で「既に存在している」というエラーの場合（ステップ2での競合）
            if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'Duplicate')) {
                Log::warning("File already exists in Vector Store, proceeding to wait step.");
            }

            Log::error("Sync failed for PDF {$pdfFile->id}: " . $e->getMessage());
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
            throw new \RuntimeException("File not found on disk: {$filePath}");
        }

        $response = Http::timeout(120)
            ->retry(3, 2000)
            ->withHeaders($this->getHeaders())
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
        $response = Http::timeout(60)
            ->retry(3, 2000)
            ->withHeaders($this->getHeaders())
            ->post("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files", [
                'file_id' => $openaiFileId,
            ]);

        if (!$response->ok()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Vector Store API error: {$error}");
        }

        return [
            'id' => $response->json('id'),
            'status' => $response->json('status'),
            'error_message' => null,
        ];
    }

    private function waitForCompletion(string $vectorStoreFileId): array
    {
        $maxAttempts = 150; // 5分間待機

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            sleep(2);

            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files/{$vectorStoreFileId}");

            if (!$response->ok()) {
                continue;
            }

            $status = $response->json('status');
            if (in_array($status, ['completed', 'failed', 'cancelled'])) {
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

    /**
     * ベクターストアの全アタッチメントと、OpenAI上の全ファイルをクリーンアップします。
     */
    public function clearAllOpenAIFiles(): array
    {
        $stats = ['vector_store' => 0, 'storage' => 0];

        // 1. ベクターストアからの解除
        $after = null;
        do {
            $url = "https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files?limit=100";
            if ($after)
                $url .= "&after={$after}";

            $response = Http::withHeaders($this->getHeaders())->get($url);
            if (!$response->ok())
                break;

            foreach ($response->json('data') as $file) {
                $fileId = $file['id'];
                $del = Http::withHeaders($this->getHeaders())
                    ->delete("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files/{$fileId}");
                if ($del->ok())
                    $stats['vector_store']++;
            }
            $after = $response->json('last_id');
            $hasMore = $response->json('has_more');
        } while ($hasMore);

        // 2. OpenAI Files ストレージからの本体削除 (purpose=assistants)
        $response = Http::withHeaders($this->getHeaders())->get('https://api.openai.com/v1/files');
        if ($response->ok()) {
            foreach ($response->json('data') as $file) {
                if ($file['purpose'] === 'assistants') {
                    $fileId = $file['id'];
                    $del = Http::withHeaders($this->getHeaders())->delete("https://api.openai.com/v1/files/{$fileId}");
                    if ($del->ok())
                        $stats['storage']++;
                }
            }
        }

        return $stats;
    }
}
