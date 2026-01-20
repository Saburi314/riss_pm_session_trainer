<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateExerciseRequest;
use App\Http\Requests\ScoreExerciseRequest;
use App\Services\ExerciseService;
use App\Services\PromptService;


class ExerciseController extends Controller
{
    public function index()
    {
        return view('exercise.index');
    }

    public function generate(GenerateExerciseRequest $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');

        $prompt = $promptService->buildGeneratePrompt($category, $subcategory);

        $exerciseText = $exerciseService->generateExercise($prompt);

        return response()->json([
            'status' => 'success',
            'category' => $category,
            'subcategory' => $subcategory,
            'exerciseText' => $exerciseText,
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
