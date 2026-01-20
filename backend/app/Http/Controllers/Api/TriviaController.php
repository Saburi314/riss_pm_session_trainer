<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityTrivia;
use Illuminate\Http\Request;

class TriviaController extends Controller
{
    /**
     * トリビアのリストをまとめて取得する
     */
    public function randomList(Request $request)
    {
        $category = $request->input('category');

        $query = SecurityTrivia::query();

        // カテゴリの指定がある場合は、カテゴリに関連したトリビアを取得する
        if ($category) {
            $query->where('category', $category);
        }

        // トリビアを10件まとめて取得
        $trivias = $query->inRandomOrder()->limit(10)->get();

        return response()->json($trivias);
    }
}
