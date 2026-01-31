<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateExerciseRequest;
use App\Http\Requests\ScoreExerciseRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\StudyLog;
use App\Services\ExerciseService;
use App\Services\PromptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'past_paper');
        $categories = Category::getDisplayData();

        $exerciseText = null;
        $category = null;
        $subcategory = null;
        $answerData = null;

        // 再挑戦用: DBから取得
        $retakeLogId = $request->query('retake_log_id');
        if ($retakeLogId) {
            $log = StudyLog::find($retakeLogId);
            if ($log && $log->user_id === Auth::id()) {
                $answerData = $log->answer_data;
                $exerciseText = $answerData['exercise_text'] ?? null;
                $category = $log->subcategory?->category?->code;
                $subcategory = $log->subcategory?->code;
            }
        }

        $pastPapers = null;
        if ($mode === 'past_paper') {
            $pastPapers = \App\Models\PastPaper::where('doc_type', 'question') // 重複排除のため問題ファイルのみ取得
                ->whereIn('exam_period', ['pm', 'pm1', 'pm2'])
                ->orderBy('year', 'desc')
                ->orderBy('season', 'desc')
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'year' => $p->year,
                        'gengo' => $p->getYearGengo(),
                        'season' => $p->season,
                        'season_name' => $p->getSeasonName(),
                        'period' => $p->exam_period,
                        'period_name' => $p->getPeriodName(),
                    ];
                });
        }

        return view('exercise.index', compact('categories', 'mode', 'pastPapers', 'exerciseText', 'category', 'subcategory', 'answerData'));
    }

    public function viewPdf(\App\Models\PastPaper $pdf)
    {
        $path = $pdf->getFullStoragePath();
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf->filename . '"'
        ]);
    }

    public function getForm(\App\Models\PastPaper $pdf)
    {
        return response()->json([
            'status' => 'success',
            'form' => $pdf->questions()->first()?->data
        ]);
    }

    /**
     * 問題データと解答済み問題を取得
     */
    public function getQuestions(\App\Models\PastPaper $pdf)
    {
        $questionsData = $pdf->questions()->first()?->data;

        // 解答済みの問題番号を取得
        $solvedQuestions = [];
        if (Auth::check()) {
            $solvedQuestions = StudyLog::where('user_id', Auth::id())
                ->where('past_paper_id', $pdf->id)
                ->get()
                ->map(function ($log) {
                    return $log->answer_data['question_number'] ?? null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        return response()->json([
            'status' => 'success',
            'questions_data' => $questionsData,
            'solved_questions' => $solvedQuestions,
        ]);
    }

    public function generate(GenerateExerciseRequest $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        if (Auth::check()) {
            $todayCount = StudyLog::where('user_id', Auth::id())
                ->whereDate('created_at', now()->toDateString())
                ->whereDate('created_at', now()->toDateString())
                ->where(function ($query) {
                    $query->whereNotNull('past_paper_id')
                        ->orWhereNotNull('ai_question_id')
                        ->orWhereNotNull('exercise_text');
                })
                ->count();

            if (!Auth::user()->isAdmin() && $todayCount >= 20) {
                return response()->json([
                    'status' => 'error',
                    'message' => '本日の利用上限に達しました。明日またお試しください。'
                ], 403);
            }
        }

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');

        $prompt = $promptService->buildGeneratePrompt($category, $subcategory);

        $exerciseText = $exerciseService->generateExercise($prompt);

        // 学習履歴の初期保存
        $logId = null;
        if (Auth::check()) {
            [$catModel, $subModel] = $exerciseService->resolveCategoryModels($category, $subcategory);

            $log = StudyLog::create([
                'user_id' => Auth::id(),
                'category_id' => $catModel?->id,
                'subcategory_id' => $subModel?->id,
                'exercise_text' => $exerciseText,
            ]);
            $logId = $log->id;
        }

        return response()->json([
            'status' => 'success',
            'category' => $category,
            'subcategory' => $subcategory,
            'exerciseText' => $exerciseText,
            'log_id' => $logId,
        ]);
    }

    public function recordGeneration(Request $request, ExerciseService $exerciseService)
    {
        $request->validate([
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'exercise_text' => 'required|string',
            'past_paper_id' => 'nullable|integer',
            'question_number' => 'nullable|integer',
        ]);

        if (!Auth::check()) {
            return response()->json(['status' => 'guest']);
        }

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $pastPaperId = $request->input('past_paper_id');
        $questionNumber = $request->input('question_number');
        $exerciseType = $pastPaperId ? 'past_paper' : 'ai_generated';

        [$catModel, $subModel] = $exerciseService->resolveCategoryModels($category, $subcategory);

        $log = StudyLog::create([
            'user_id' => Auth::id(),
            'category_id' => $catModel?->id,
            'subcategory_id' => $subModel?->id,
            'past_paper_id' => $pastPaperId,
            'exercise_text' => $request->input('exercise_text'),
            'answer_data' => [
                'question_number' => $questionNumber,
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'log_id' => $log->id,
        ]);
    }

    public function score(ScoreExerciseRequest $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $exerciseText = $request->input('exercise_text', '');
        $userAnswer = $request->input('user_answer', '');

        // 過去問モードの場合の追加情報
        $pastPaperId = $request->input('past_paper_id');
        $questionNumber = $request->input('question_number');
        $answerDetails = $request->input('answer_details'); // array (casted from JSON)

        // 模範解答を取得（過去問モードのみ）
        $sampleAnswers = [];
        if ($pastPaperId && $questionNumber) {
            $pdf = \App\Models\PastPaper::find($pastPaperId);
            if ($pdf) {
                // PastPaperAnswerのdata JSON内に question_number がある形式を想定
                $sampleAnswers = \App\Models\PastPaperAnswer::where('past_paper_id', $pdf->id)
                    ->get()
                    ->filter(function ($a) use ($questionNumber) {
                        return ($a->data['question_number'] ?? null) == $questionNumber;
                    })
                    ->values()
                    ->toArray();
            }
        }

        // プロンプト生成（模範解答を含める）
        $prompt = $promptService->buildScorePrompt($exerciseText, $userAnswer, $category, $subcategory, $sampleAnswers);

        // AI採点実行
        $scoringResult = $exerciseService->scoreExercise($prompt);

        // 学習履歴の更新
        $logId = $request->input('log_id');
        if (Auth::check()) {
            $score = $exerciseService->extractScore($scoringResult);

            if ($logId) {
                $log = StudyLog::find($logId);
                if ($log && $log->user_id === Auth::id()) {
                    $newAnswerData = array_merge($log->answer_data ?? [], [
                        'user_answer' => $userAnswer,
                        'answer_details' => $answerDetails,
                        'question_number' => $questionNumber,
                    ]);

                    $log->update([
                        'score' => $score,
                        'feedback' => $scoringResult,
                        'user_answer' => $userAnswer,
                        'answer_data' => $newAnswerData,
                    ]);
                }
            } else {
                // 新規作成
                $exerciseType = $pastPaperId ? 'past_paper' : 'ai_generated';
                [$catModel, $subModel] = $exerciseService->resolveCategoryModels($category, $subcategory);

                StudyLog::create([
                    'user_id' => Auth::id(),
                    'category_id' => $catModel?->id,
                    'subcategory_id' => $subModel?->id,
                    'past_paper_id' => $pastPaperId,
                    'score' => $score,
                    'feedback' => $scoringResult,
                    'exercise_text' => $exerciseText,
                    'user_answer' => $userAnswer,
                    'answer_data' => [
                        'answer_details' => $answerDetails,
                        'question_number' => $questionNumber,
                    ],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'category' => $category,
            'subcategory' => $subcategory,
            'exerciseText' => $exerciseText,
            'userAnswer' => $userAnswer,
            'scoringResult' => $scoringResult,
            'score' => $score ?? null,
        ]);
    }
}
