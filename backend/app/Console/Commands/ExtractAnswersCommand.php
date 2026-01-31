<?php

namespace App\Console\Commands;

use App\Models\PastPaper;
use App\Models\PastPaperAnswer;
use App\Services\PdfAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtractAnswersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'answers:extract {--id= : PastPaper ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract draft answers from OCR text of answer/commentary PDFs using AI';

    /**
     * Execute the console command.
     */
    public function handle(PdfAnalysisService $analysisService)
    {
        $id = $this->option('id');

        $query = PastPaper::query()
            ->whereIn('doc_type', ['answer', 'commentary'])
            ->whereNotNull('searchable_text_path');

        if ($id) {
            $query->where('id', $id);
        }

        $papers = $query->get();

        if ($papers->isEmpty()) {
            $this->info("No eligible PastPaper files found.");
            return 0;
        }

        $this->info("Found {$papers->count()} papers to process.");

        foreach ($papers as $paper) {
            $this->info("Processing [{$paper->id}] {$paper->filename}...");

            try {
                $questions = $analysisService->generateDraftAnswers($paper);

                if (empty($questions)) {
                    $this->warn("  No questions extracted.");
                    continue;
                }

                DB::transaction(function () use ($paper, $questions) {
                    foreach ($questions as $qData) {
                        $qNum = $qData['question_number'] ?? null;
                        if (!$qNum)
                            continue;

                        // 既存の解答データを検索（JSON内のquestion_numberで一致判定は難しいため、メモリ上でフィルタリングするか、一旦全削除して再作成かの選択）
                        // ここでは、データ量が少ないため、該当Paperの全解答を取得して一致するものを探す方式を採用

                        $existing = PastPaperAnswer::where('past_paper_id', $paper->id)->get();
                        $target = $existing->first(function ($ans) use ($qNum) {
                            return ($ans->data['question_number'] ?? null) == $qNum;
                        });

                        if ($target) {
                            $target->update([
                                'data' => $qData,
                                'ai_draft_generated_at' => now(),
                            ]);
                            $this->line("  Updated Question {$qNum}");
                        } else {
                            PastPaperAnswer::create([
                                'past_paper_id' => $paper->id,
                                'data' => $qData,
                                'ai_draft_generated_at' => now(),
                            ]);
                            $this->line("  Created Question {$qNum}");
                        }
                    }
                });

                $this->info("  Done.");

            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
            }
        }

        return 0;
    }
}
