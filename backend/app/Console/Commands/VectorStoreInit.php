<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VectorStoreInit extends Command
{
    protected $signature = 'vs:init';
    protected $description = 'Create OpenAI Vector Store and save ID to .env';

    public function handle(): int
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            $this->error('OPENAI_API_KEY is not set.');
            return self::FAILURE;
        }

        $res = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/vector_stores', [
                'name' => 'RISS PM Past Exams',
            ]);

        if (!$res->ok()) {
            $this->error('Failed to create vector store: ' . $res->body());
            return self::FAILURE;
        }

        $id = $res->json('id');

        $this->info("Vector Store created: {$id}");
        $this->info("Set this value to OPENAI_VECTOR_STORE_ID in your .env");

        return self::SUCCESS;
    }
}
