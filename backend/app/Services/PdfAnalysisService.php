<?php

namespace App\Services;

use App\Models\PdfFile;
use App\Models\Question;
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
    public function analyze(PdfFile $pdfFile): array
    {
        $this->validateConfig();

        Log::info("Analyzing PDF: {$pdfFile->filename} (Type: {$pdfFile->doc_type})");

        $result = [];

        if ($pdfFile->doc_type === 'question') {
            // 問題冊子（画像形式のPDF）は Vision API で OCR 解析
            $result = $this->analyzeWithVision($pdfFile);
        } else {
            // 解答例・講評（テキスト形式のPDF）は pdftotext でテキスト抽出
            $text = $this->extractTextDirectly($pdfFile);
            $result = [
                'content' => $text,
                'questions' => []
            ];
        }

        // 検索用テキストファイルを保存
        $this->saveSearchableText($pdfFile, $result['content'] ?? '');

        return $result;
    }

    /**
     * GPT-4o Vision を使用して画像PDFをOCR解析 (バッチ処理対応)
     */
    private function analyzeWithVision(PdfFile $pdfFile): array
    {
        $imagePaths = $this->convertPdfToImages($pdfFile);

        if (empty($imagePaths)) {
            throw new RuntimeException("PDFの画像変換に失敗しました: {$pdfFile->filename}");
        }

        $allContent = "";
        $allQuestions = [];

        // 1ページずつのバッチで処理
        $chunks = array_chunk($imagePaths, 1);
        $prompt = RissPrompts::getAnalyzePrompt();

        foreach ($chunks as $index => $batch) {
            $pageNum = $index + 1;
            Log::info("Processing OCR (Page {$pageNum}/" . count($chunks) . ") for {$pdfFile->filename}");

            $messages = [
                ['role' => 'user', 'content' => [['type' => 'text', 'text' => "【指示: 第{$pageNum}ページ】\n画像内の全テキストを、要約せず、一文字も漏らさず、原本通りに書き出してください。出力は必ず指定のJSON形式を守ってください。\n\n" . $prompt]]]
            ];

            foreach ($batch as $path) {
                $base64Image = base64_encode(file_get_contents($path));
                $messages[0]['content'][] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => "data:image/jpeg;base64,{$base64Image}", 'detail' => 'high']
                ];
            }

            $payload = [
                'model' => 'gpt-4o-2024-08-06',
                'messages' => $messages,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object']
            ];

            // 30,000 TPM (Tokens Per Minute) 制限を回避するためのウェイト (5秒)
            if ($index > 0) {
                Log::info("Rate limit avoidance: Waiting 5 seconds...");
                usleep(5000000);
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(600)->withToken($this->apiKey)->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error("AI解析エラー (Page {$pageNum})", ['body' => $response->body()]);
                continue;
            }

            $rawContent = (string) $response->json('choices.0.message.content');

            // Markdown の除去
            $cleanJson = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($rawContent));
            $data = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON解析エラー (Page {$pageNum})", ['error' => json_last_error_msg(), 'content' => $rawContent]);
                // JSONが壊れていても、単なるテキストとして救済（最低限コンテンツは確保）
                $allContent .= "\n[Page {$pageNum} Parsing Failed, Original Data below]\n" . $rawContent . "\n";
                continue;
            }

            $allContent .= ($data['content'] ?? '') . "\n\n";
            if (!empty($data['questions'])) {
                $allQuestions = array_merge($allQuestions, $data['questions']);
            }
        }

        // 一時ファイルの削除
        foreach ($imagePaths as $path) {
            if (file_exists($path))
                unlink($path);
            $dir = dirname($path);
            if (is_dir($dir) && count(scandir($dir)) == 2)
                rmdir($dir);
        }

        return [
            'content' => trim($allContent),
            'questions' => $allQuestions
        ];
    }

    /**
     * テキストベースのPDFから直接テキストを抽出
     */
    private function extractTextDirectly(PdfFile $pdfFile): string
    {
        $pdfPath = $pdfFile->getFullStoragePath();
        $cmd = "pdftotext -layout \"{$pdfPath}\" -";

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warn("pdftotext failed for {$pdfFile->filename}, falling back to empty string.");
            return "";
        }

        return implode("\n", $output);
    }

    /**
     * 検索用テキストファイルを保存する
     */
    private function saveSearchableText(PdfFile $pdfFile, string $content): void
    {
        if (empty(trim($content)))
            return;

        $dir = storage_path('app/searchable_texts');
        if (!file_exists($dir))
            mkdir($dir, 0777, true);

        $filename = $pdfFile->filename . '.txt';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $content);

        // DBに相対パスを保存（storage/app からの相対パス）
        $relativePath = 'searchable_texts/' . $filename;
        $pdfFile->update(['searchable_text_path' => $relativePath]);

        Log::info("Searchable text saved: {$path} (DB path: {$relativePath})");
    }

    private function validateConfig(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API Key is missing.');
        }
    }

    private function convertPdfToImages(PdfFile $pdfFile): array
    {
        $pdfPath = $pdfFile->getFullStoragePath();
        $tempDir = storage_path('app/temp/pdf_images/' . uniqid());
        if (!is_dir($tempDir))
            mkdir($tempDir, 0777, true);

        $outputPrefix = $tempDir . '/page';

        // 重要: 問題冊子の全容を把握するため、一旦全ページ（または主要ページ）を対象にする
        // 解像度を 200DPI, 横幅 1600px に引き上げ、小さな文字の認識精度を向上させる
        // 全ての試験冊子をカバーするため、上限を 50 ページに設定
        $cmd = "pdftoppm -jpeg -r 200 -scale-to 1600 -l 50 \"{$pdfPath}\" \"{$outputPrefix}\"";

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0)
            return [];

        $images = glob($tempDir . '/*.jpg');
        sort($images);

        return $images;
    }
}
