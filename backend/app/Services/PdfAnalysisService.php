<?php

namespace App\Services;

use App\Models\PastPaper;
use App\Models\PastPaperQuestion;
use App\Prompts\RissPrompts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfAnalysisService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
        $this->model = 'gpt-4o'; // Vision能力が高い最新モデルを使用
    }

    /**
     * PDFファイルから設問構造を抽出し、検索用テキストを生成する
     */
    public function analyze(PastPaper $pastPaper): array
    {
        $this->validateConfig();

        Log::info("Analyzing PDF: {$pastPaper->filename} (Type: {$pastPaper->doc_type})");

        $result = [];

        if ($pastPaper->doc_type === 'question') {
            // 現在は Vision 解析を廃止し、既存テキストからの抽出を推奨
            throw new RuntimeException("画像PDFの直接解析は廃止されました。「AI設問抽出 (テキスト)」を使用してください。");
        } else {
            // 解答例・講評（テキスト形式のPDF）は pdftotext でテキスト抽出
            $text = $this->extractTextDirectly($pastPaper);
            $result = [
                'content' => $text,
                'questions' => []
            ];
        }

        // 検索用テキストファイルを保存
        $this->saveSearchableText($pastPaper, $result['content'] ?? '');

        return $result;
    }

    /**
     * すでに存在するOCRテキストから設問構造を抽出する
     */
    public function analyzeFromText(PastPaper $pastPaper): array
    {
        $this->validateConfig();

        $textPath = $pastPaper->getSearchableTextPath();
        if (!$textPath || !file_exists($textPath)) {
            throw new RuntimeException("解析済みのテキストが見つかりません。先にOCR解析を実行してください。");
        }

        $content = file_get_contents($textPath);
        Log::info("Analyzing existing text for: {$pastPaper->filename}");

        $prompt = RissPrompts::getAnalyzeFromTextPrompt();

        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => "以下は情報処理安全確保支援士の試験問題のOCR結果です。このテキストから設問構造をJSON形式で抽出してください。\n\n" . $prompt . "\n\n【解析対象のテキスト】\n" . $content]
            ],
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object']
        ];

        $response = Http::timeout(600)->withToken($this->apiKey)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new RuntimeException("AIによるテキスト解析に失敗しました: " . $response->body());
        }

        $rawContent = (string) $response->json('choices.0.message.content');
        $cleanJson = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($rawContent));
        $data = json_decode($cleanJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON解析エラー: " . json_last_error_msg());
        }

        return [
            'content' => $content,
            'questions' => $data['questions'] ?? []
        ];
    }

    /**
     * テキストベースのPDFから直接テキストを抽出
     */
    private function extractTextDirectly(PastPaper $pastPaper): string
    {
        $pdfPath = $pastPaper->getFullStoragePath();
        $cmd = "pdftotext -layout \"{$pdfPath}\" -";

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warn("pdftotext failed for {$pastPaper->filename}, falling back to empty string.");
            return "";
        }

        return implode("\n", $output);
    }

    /**
     * 検索用テキストファイルを保存する
     */
    private function saveSearchableText(PastPaper $pastPaper, string $content): void
    {
        if (empty(trim($content)))
            return;

        $dir = storage_path('app/searchable_texts');
        if (!file_exists($dir))
            mkdir($dir, 0777, true);

        $filename = $pastPaper->filename . '.txt';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $content);

        // DBに相対パスを保存（storage/app からの相対パス）
        $relativePath = 'searchable_texts/' . $filename;
        $pastPaper->update(['searchable_text_path' => $relativePath]);

        Log::info("Searchable text saved: {$path} (DB path: {$relativePath})");
    }

    /**
     * テキスト解析済みの解答・講評PDFから模範解答ドラフトを生成する
     */
    public function generateDraftAnswers(PastPaper $pastPaper): array
    {
        $this->validateConfig();

        if (!in_array($pastPaper->doc_type, ['answer', 'commentary'])) {
            throw new RuntimeException("解答・講評PDF以外はサポートされていません。");
        }

        $textPath = $pastPaper->getSearchableTextPath();
        if (!$textPath || !file_exists($textPath)) {
            throw new RuntimeException("解析済みのテキストが見つかりません。先にOCR解析を実行してください。");
        }

        $content = file_get_contents($textPath);
        Log::info("Generating draft answers for: {$pastPaper->filename}");

        $prompt = RissPrompts::getExtractAnswersPrompt();

        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => "以下は情報処理安全確保支援士の試験解答/講評のテキストです。このテキストから模範解答をJSON形式で抽出してください。\n\n" . $prompt . "\n\n【対象テキスト】\n" . $content]
            ],
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object']
        ];

        $response = Http::timeout(600)->withToken($this->apiKey)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new RuntimeException("AIによる解答生成に失敗しました: " . $response->body());
        }

        $rawContent = (string) $response->json('choices.0.message.content');
        $cleanJson = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($rawContent));
        $data = json_decode($cleanJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON解析エラー: " . json_last_error_msg());
        }

        // data は配列（問題リスト）であることを期待
        // [ { "question_number": 1, ... }, ... ]
        $questions = $data['questions'] ?? $data; // ルートが配列の場合と { questions: [] } の場合に対応

        return is_array($questions) ? $questions : [];
    }

    private function validateConfig(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API Key is missing.');
        }
    }
}
