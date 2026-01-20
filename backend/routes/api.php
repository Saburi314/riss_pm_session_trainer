<?php

use App\Http\Controllers\Api\TriviaController;

Route::get('/trivia/random', [TriviaController::class, 'random'])->name('api.trivia.random');
