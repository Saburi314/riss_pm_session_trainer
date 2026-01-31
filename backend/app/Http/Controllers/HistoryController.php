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
        $logs = StudyLog::with(['subcategory.category', 'pastPaper'])
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

        $history->load(['subcategory.category', 'pastPaper']);

        return view('history.show', compact('history'));
    }
}
