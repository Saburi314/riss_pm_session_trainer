<?php

namespace App\Http\Controllers;

use App\Models\StudyLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    public function index()
    {
        $logs = StudyLog::with('subcategory.category')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('history.index', compact('logs'));
    }

    public function show(StudyLog $history)
    {
        if ($history->user_id !== Auth::id()) {
            abort(403);
        }

        return view('history.show', compact('history'));
    }

    public function analysis()
    {
        $userId = Auth::id();

        // カテゴリ・サブカテゴリごとの集計
        $analytics = StudyLog::query()
            ->leftJoin('categories', 'study_logs.category_id', '=', 'categories.id')
            ->leftJoin('subcategories', 'study_logs.subcategory_id', '=', 'subcategories.id')
            ->select([
                DB::raw('COALESCE(categories.name, "全般") as category_name'),
                DB::raw('COALESCE(subcategories.name, "全般") as subcategory_name'),
                'categories.code as category_code',
                'subcategories.code as subcategory_code',
                DB::raw('AVG(score) as average_score'),
                DB::raw('COUNT(*) as count')
            ])
            ->where('study_logs.user_id', $userId)
            ->whereNotNull('score')
            ->groupBy('category_name', 'subcategory_name', 'category_code', 'subcategory_code')
            ->orderBy('average_score', 'asc')
            ->get();

        // 弱点（スコアが低い順）上位3件を「おすすめ」として抽出
        $recommendations = $analytics->where('average_score', '<', 80)->take(3);

        return view('history.analysis', compact('analytics', 'recommendations'));
    }
}
