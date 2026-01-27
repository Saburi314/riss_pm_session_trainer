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

        // 再挑戦用: DBから取得
        $retakeLogId = $request->query('retake_log_id');
        if ($retakeLogId) {
            $log = StudyLog::find($retakeLogId);
            if ($log && $log->user_id === Auth::id()) {
                $exerciseText = $log->exercise_text;
                $category = $log->subcategory?->category?->code;
                $subcategory = $log->subcategory?->code;
            }
        }

        $pastPapers = null;
        if ($mode === 'past_paper') {
            $pastPapers = \App\Models\PdfFile::where('doc_type', 'question') // 重複排除のため問題ファイルのみ取得
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

        return view('exercise.index', compact('categories', 'mode', 'pastPapers', 'exerciseText', 'category', 'subcategory'));
    }

    public function viewPdf(\App\Models\PdfFile $pdf)
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

    public function getForm(\App\Models\PdfFile $pdf)
    {
        return response()->json([
            'status' => 'success',
            'form' => $pdf->answer_form_json
        ]);
    }

    public function generate(GenerateExerciseRequest $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        if (Auth::check()) {
            $todayCount = StudyLog::where('user_id', Auth::id())
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('exercise_type', ['ai_generated', 'past_paper']) // 採点や生成の合計
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
                'exercise_type' => 'ai_generated',
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
            'pdf_file_id' => 'nullable|integer',
        ]);

        if (!Auth::check()) {
            return response()->json(['status' => 'guest']);
        }

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $pdfFileId = $request->input('pdf_file_id');
        $exerciseType = $pdfFileId ? 'past_paper' : 'ai_generated';

        [$catModel, $subModel] = $exerciseService->resolveCategoryModels($category, $subcategory);

        $log = StudyLog::create([
            'user_id' => Auth::id(),
            'category_id' => $catModel?->id,
            'subcategory_id' => $subModel?->id,
            'exercise_text' => $request->input('exercise_text'),
            'pdf_file_id' => $pdfFileId,
            'exercise_type' => $exerciseType,
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

        $prompt = $promptService->buildScorePrompt($exerciseText, $userAnswer, $category, $subcategory);

        $scoringResult = $exerciseService->scoreExercise($prompt);

        // 学習履歴の更新
        $logId = $request->input('log_id');
        if (Auth::check()) {
            $score = $exerciseService->extractScore($scoringResult);

            if ($logId) {
                $log = StudyLog::find($logId);
                if ($log && $log->user_id === Auth::id()) {
                    $log->update([
                        'user_answer' => $userAnswer,
                        'score' => $score,
                        'feedback' => $scoringResult,
                    ]);
                }
            } else {
                // 過去問演習などの場合、ここで新規作成されることがある
                $pdfFileId = $request->input('pdf_file_id');
                $exerciseType = $pdfFileId ? 'past_paper' : 'ai_generated';

                [$catModel, $subModel] = $exerciseService->resolveCategoryModels($category, $subcategory);

                StudyLog::create([
                    'user_id' => Auth::id(),
                    'category_id' => $catModel?->id,
                    'subcategory_id' => $subModel?->id,
                    'exercise_text' => $exerciseText,
                    'user_answer' => $userAnswer,
                    'score' => $score,
                    'feedback' => $scoringResult,
                    'pdf_file_id' => $pdfFileId,
                    'exercise_type' => $exerciseType,
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
        ]);
    }

}
