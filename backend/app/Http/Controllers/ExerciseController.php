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
            'major_category' => ['nullable', 'string', 'in:' . implode(',', array_keys(PromptService::CATEGORIES))],
            'minor_category' => ['nullable', 'string'],
        ]);

        $major = $request->input('major_category');
        $minor = $request->input('minor_category');

        $prompt = $promptService->buildGeneratePrompt($major, $minor);

        $exerciseText = $exerciseService->generateExercise($prompt);

        return view('exercise.index', [
            'major_category' => $major,
            'minor_category' => $minor,
            'exerciseText' => $exerciseText,
        ]);
    }

    public function score(Request $request, ExerciseService $exerciseService, PromptService $promptService)
    {
        $request->validate([
            'major_category' => ['nullable', 'string'],
            'minor_category' => ['nullable', 'string'],
            'exercise_text' => ['required', 'string', 'max:80000'],
            'user_answer' => ['required', 'string', 'max:20000'],
        ]);

        $major = $request->input('major_category');
        $minor = $request->input('minor_category');
        $exerciseText = $request->input('exercise_text', '');
        $userAnswer = $request->input('user_answer', '');

        $prompt = $promptService->buildScorePrompt($exerciseText, $userAnswer, $major, $minor);

        $scoringResult = $exerciseService->scoreExercise($prompt);

        return view('exercise.index', [
            'major_category' => $major,
            'minor_category' => $minor,
            'exerciseText' => $exerciseText,
            'userAnswer' => $userAnswer,
            'scoringResult' => $scoringResult,
        ]);
    }
}
