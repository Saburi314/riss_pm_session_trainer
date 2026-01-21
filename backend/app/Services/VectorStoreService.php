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

    public function syncFile(PdfFile $pdfFile): array
    {
        if (!$this->apiKey || !$this->vectorStoreId) {
            throw new \RuntimeException('OpenAI API key or Vector Store ID is not configured.');
        }

        try {
            Log::info("Starting sync for PDF: {$pdfFile->filename}");

            // 1. Upload to Files API
            $openaiFileId = $this->uploadToFilesApi($pdfFile);
            Log::info("Uploaded to Files API: {$openaiFileId}");

            // 2. Add to Vector Store
            $result = $this->addToVectorStore($openaiFileId, $pdfFile);
            Log::info("Added to Vector Store: {$result['id']} (status: {$result['status']})");

            $pdfFile->update([
                'openai_file_id' => $openaiFileId,
                'vector_store_file_id' => $result['id'],
                'index_status' => $result['status'],
                'indexed_at' => $result['status'] === 'completed' ? now() : null,
                'error_message' => $result['error_message'],
            ]);

            return $result;

        } catch (\Exception $e) {
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

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(120)
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
        $attributes = [
            'year' => (string) $pdfFile->year,
            'season' => $pdfFile->season,
            'exam_period' => $pdfFile->exam_period,
            'doc_type' => $pdfFile->doc_type,
        ];

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(60)
            ->withHeaders($this->getHeaders())
            ->post("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files", [
                'file_id' => $openaiFileId,
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

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get("https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files/{$vectorStoreFileId}");

            if (!$response->ok()) {
                continue;
            }

            $status = $response->json('status');

            if ($status === 'completed' || $status === 'failed' || $status === 'cancelled') {
                return [
                    'id' => $vectorStoreFileId,
                    'status' => $status,
                    'last_error' => $response->json('last_error'),
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
