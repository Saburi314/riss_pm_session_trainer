<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToProvider'])
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'handleProviderCallback'])
        ->name('social.callback');
});

Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/verify-email', [AuthController::class, 'showVerifyNotice'])->name('verification.notice');
    Route::get('/verify-resend', [AuthController::class, 'showResendForm'])->name('verification.resend');
    Route::post('/verify-resend', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');
    Route::delete('/profile/withdraw', [ProfileController::class, 'withdraw'])->name('profile.withdraw');

    Route::middleware('verified')->group(function () {
        Route::get('/exercise', [ExerciseController::class, 'index'])->name('exercise.index');
        Route::post('/exercise/generate', [ExerciseController::class, 'generate'])->middleware('throttle:ai-generate')->name('exercise.generate');
        Route::post('/exercise/record-generation', [ExerciseController::class, 'recordGeneration'])->name('exercise.record-generation');
        Route::post('/exercise/score', [ExerciseController::class, 'score'])->middleware('throttle:ai-score')->name('exercise.score');

        Route::get('/exercise/pdf/{pdf}', [ExerciseController::class, 'viewPdf'])->name('exercise.pdf');
        Route::get('/exercise/form/{pdf}', [ExerciseController::class, 'getForm'])->name('exercise.form');
        Route::get('/exercise/questions/{pdf}', [ExerciseController::class, 'getQuestions'])->name('exercise.questions');

        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/{history}', [HistoryController::class, 'show'])->name('history.show');
    });
});

Route::get('/', function () {
    return redirect()->route('exercise.index');
});

