<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VectorStoreInit extends Command
{
    protected $signature = 'vs:init';
    protected $description = 'ベクトルストアを新規作成し、IDを表示します';

    public function handle(): int
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            $this->error('OpenAI API key is not configured in services.php');
            return self::FAILURE;
        }

        $this->info("ベクトルストア 'RISS PM Past Exams' を作成中...");

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'OpenAI-Beta' => 'assistants=v2',
            ])->post('https://api.openai.com/v1/vector_stores', [
                        'name' => 'RISS PM Past Exams',
                    ]);

            if (!$response->ok()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new \RuntimeException($error);
            }

            $id = $response->json('id');
            $this->info("ベクトルストアが作成されました: {$id}");
            $this->warn("このIDを .env の OPENAI_VECTOR_STORE_ID に手動で設定してください。");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('エラー: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
