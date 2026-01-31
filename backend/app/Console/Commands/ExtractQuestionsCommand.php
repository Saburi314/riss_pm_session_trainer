<?php

namespace App\Console\Commands;

use App\Models\PastPaper;
use App\Models\PastPaperQuestion;
use App\Services\PdfAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExtractQuestionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:extract {--id= : PastPaper ID to process} {--all : Process all PastPapers without questions} {--force : Reprocess even if questions exist}';

    /**
     * The message of the console command.
     *
     * @var string
     */
    protected $description = 'Extract question structure from PastPaper PDFs using AI';

    /**
     * Execute the console command.
     */
    public function handle(PdfAnalysisService $service)
    {
        $id = $this->option('id');
        $all = $this->option('all');
        $force = $this->option('force');

        if (!$id && !$all) {
            $this->error('Please specify --id or --all');
            return 1;
        }

        $query = PastPaper::where('doc_type', 'question');

        if ($id) {
            $query->where('id', $id);
        } elseif (!$force) {
            // --all かつ --force でない場合は、まだ questions がないもののみ対象
            $query->whereDoesntHave('questions');
        }

        $papers = $query->get();

        if ($papers->isEmpty()) {
            $this->info('No papers to process.');
            return 0;
        }

        $this->info("Found {$papers->count()} papers to process.");

        foreach ($papers as $paper) {
            $this->info("Processing: {$paper->filename} (ID: {$paper->id})");

            try {
                // テキストがなければ先に抽出を試みる（既存 logic に合わせる）
                if (!$paper->getSearchableTextPath() || !file_exists($paper->getSearchableTextPath())) {
                    $this->info("Extracting text first...");
                    $service->analyze($paper);
                }

                $this->info("Analyzing structure via AI...");
                $result = $service->analyzeFromText($paper);

                if (empty($result['questions'])) {
                    $this->warn("No questions extracted for {$paper->filename}");
                    continue;
                }

                // 保存
                PastPaperQuestion::updateOrCreate(
                    ['past_paper_id' => $paper->id],
                    ['data' => ['questions' => $result['questions']]]
                );

                $this->info("Successfully extracted " . count($result['questions']) . " questions.");

            } catch (\Exception $e) {
                $this->error("Error processing {$paper->filename}: " . $e->getMessage());
                Log::error("Question extraction failed for {$paper->id}: " . $e->getMessage());
            }
        }

        $this->info('Extraction process finished.');
        return 0;
    }
}
