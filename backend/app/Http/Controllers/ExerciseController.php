<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExerciseService;
use App\Services\PromptService;

class ExerciseController extends Controller
{
    public function index()
    {
        return view('exercise.index');
    }

    public function generate(Request $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $request->validate([
            'category' => ['nullable', 'string', 'in:' . implode(',', array_keys(PromptService::CATEGORIES))],
            'subcategory' => ['nullable', 'string'],
        ]);

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');

        $prompt = $promptService->buildGeneratePrompt($category, $subcategory);

        $exerciseText = $exerciseService->generateExercise($prompt);

        return view('exercise.index', [
            'category' => $category,
            'subcategory' => $subcategory,
            'exerciseText' => $exerciseText,
        ]);
    }

    public function score(Request $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $request->validate([
            'category' => ['nullable', 'string'],
            'subcategory' => ['nullable', 'string'],
            'exercise_text' => ['required', 'string', 'max:80000'],
            'user_answer' => ['required', 'string', 'max:20000'],
        ]);

        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $exerciseText = $request->input('exercise_text', '');
        $userAnswer = $request->input('user_answer', '');

        $prompt = $promptService->buildScorePrompt($exerciseText, $userAnswer, $category, $subcategory);

        $scoringResult = $exerciseService->scoreExercise($prompt);

        return view('exercise.index', [
            'category' => $category,
            'subcategory' => $subcategory,
            'exerciseText' => $exerciseText,
            'userAnswer' => $userAnswer,
            'scoringResult' => $scoringResult,
        ]);
    }
}
