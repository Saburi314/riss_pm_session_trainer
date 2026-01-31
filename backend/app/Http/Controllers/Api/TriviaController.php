<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trivia;
use Illuminate\Http\Request;

class TriviaController extends Controller
{
    /**
     * トリビアのリストをまとめて取得する
     */
    public function randomList(Request $request)
    {
        $category = $request->input('category');
        $trivias = Trivia::getRandomListByCategory($category);

        return response()->json($trivias);
    }
}
