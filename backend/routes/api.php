<?php

use App\Http\Controllers\Api\TriviaController;

Route::get('/trivia/random-list', [TriviaController::class, 'randomList'])->name('api.trivia.random_list');
