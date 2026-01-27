<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExerciseService
{
    private string $apiKey;
    private string $model;
    private string $vectorStoreId;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->model = (string) (config('services.openai.model') ?: 'gpt-5.2-2025-12-11');
        $this->vectorStoreId = (string) config('services.openai.vector_store_id');
    }

    /**
     * カテゴリとサブカテゴリのモデルをコードから解決する
     */
    public function resolveCategoryModels(?string $catCode, ?string $subCode): array
    {
        $catModel = $catCode ? Category::where('code', $catCode)->first() : null;
        $subModel = $subCode ? Subcategory::where('code', $subCode)->first() : null;

        return [$catModel, $subModel];
    }

    /**
     * 問題を生成する
     */
    public function generateExercise(string $prompt): string
    {
        return $this->callResponses($prompt, 7500, 'medium');
    }

    /**
     * 解答を採点する
     */
    public function scoreExercise(string $prompt): string
    {
        return $this->callResponses($prompt, 75000, 'low');
    }

    /**
     * OpenAI Responses API を呼び出す
     */
    private function callResponses(string $prompt, int $maxOutputTokens, string $effort = 'low'): string
    {
        $this->validateConfig();

        $tool = [
            'type' => 'file_search',
            'vector_store_ids' => [$this->vectorStoreId],
            'max_num_results' => 8,
        ];

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                    ],
                ],
            ],
            'max_output_tokens' => $maxOutputTokens,
            'reasoning' => ['effort' => $effort],
            'tools' => [$tool],
        ];

        try {
            $response = Http::timeout(180)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', $payload);

            if (!$response->ok()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'payload' => $payload
                ]);
                return "APIエラーが発生しました (HTTP {$response->status()})。";
            }

            $data = $response->json();
            return $this->parseResponseText($data);

        } catch (\Exception $e) {
            Log::error('OpenAI Connection Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "AIサービスとの通信に失敗しました。しばらく時間を置いてから再度お試しください。";
        }
    }

    /**
     * 設定のバリデーション
     */
    private function validateConfig(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY が設定されていません。');
        }
        if (empty($this->vectorStoreId)) {
            throw new RuntimeException('OPENAI_VECTOR_STORE_ID が設定されていません。');
        }
    }

    /**
     * APIレスポンスからテキストを抽出する
     */
    private function parseResponseText(array $data): string
    {
        $outputText = '';

        foreach (($data['output'] ?? []) as $outputItem) {
            foreach (($outputItem['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text') {
                    $outputText .= (string) ($content['text'] ?? '');
                }
            }
        }

        if (empty($outputText)) {
            Log::warning('OpenAI API: No output_text found in response', ['response' => $data]);
            return "AIからの応答が空でした。もう一度お試しください。";
        }

        return $outputText;
    }

    /**
     * AIの回答テキストからスコアを抽出する
     */
    public function extractScore(string $text): ?int
    {
        if (preg_match('/(?:Score|点数|スコア)[:：]\s*(\d+)/u', $text, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
