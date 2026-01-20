<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityTrivia;
use Illuminate\Http\Request;

class TriviaController extends Controller
{
    public function random(Request $request)
    {
        $category = $request->input('category');

        $query = SecurityTrivia::query();

        if ($category) {
            $query->where('category', $category);
        }

        // カテゴリに該当がない場合は全件から取得
        if ($query->count() === 0) {
            $query = SecurityTrivia::query();
        }

        $trivia = $query->inRandomOrder()->first();

        return response()->json($trivia);
    }
}
