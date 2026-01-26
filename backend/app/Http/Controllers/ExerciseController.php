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
    public function index()
    {
        $categories = Category::getDisplayData();
        return view('exercise.index', compact('categories'));
    }

    public function generate(GenerateExerciseRequest $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');

        $prompt = $promptService->buildGeneratePrompt($category, $subcategory);

        $exerciseText = $exerciseService->generateExercise($prompt);

        // 学習履歴の初期保存
        $logId = null;
        if (Auth::check()) {
            [$catModel, $subModel] = $this->findCategoryAndSubcategory($category, $subcategory);

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

    public function recordGeneration(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'exercise_text' => 'required|string',
        ]);

        if (!Auth::check()) {
            return response()->json(['status' => 'guest']);
        }

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        [$catModel, $subModel] = $this->findCategoryAndSubcategory($category, $subcategory);

        $log = StudyLog::create([
            'user_id' => Auth::id(),
            'category_id' => $catModel?->id,
            'subcategory_id' => $subModel?->id,
            'exercise_text' => $request->input('exercise_text'),
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
            // スコアの抽出 (Score: XX または 点数：XX または スコア：XX)
            $score = null;
            if (preg_match('/(?:Score|点数|スコア)[:：]\s*(\d+)/u', $scoringResult, $matches)) {
                $score = (int) $matches[1];
            }

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
                // 万が一ログがない場合は新規作成
                [$catModel, $subModel] = $this->findCategoryAndSubcategory($category, $subcategory);

                StudyLog::create([
                    'user_id' => Auth::id(),
                    'category_id' => $catModel?->id,
                    'subcategory_id' => $subModel?->id,
                    'exercise_text' => $exerciseText,
                    'user_answer' => $userAnswer,
                    'score' => $score,
                    'feedback' => $scoringResult,
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

    private function findCategoryAndSubcategory(?string $catCode, ?string $subCode): array
    {
        $catModel = $catCode ? Category::where('code', $catCode)->first() : null;
        $subModel = $subCode ? Subcategory::where('code', $subCode)->first() : null;

        return [$catModel, $subModel];
    }
}
