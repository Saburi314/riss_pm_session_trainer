<?php

use App\Http\Controllers\ExerciseController;

Route::get('/exercise', [ExerciseController::class, 'index'])->name('exercise.index');
Route::post('/exercise/generate', [ExerciseController::class, 'generate'])->name('exercise.generate');
Route::post('/exercise/score', [ExerciseController::class, 'score'])->name('exercise.score');

