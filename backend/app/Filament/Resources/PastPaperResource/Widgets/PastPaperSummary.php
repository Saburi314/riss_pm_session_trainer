<?php

namespace App\Filament\Resources\PastPaperResource\Widgets;

use App\Models\PastPaper;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PastPaperSummary extends Widget
{
    protected static string $view = 'filament.resources.past-paper-resource.widgets.past-paper-summary';

    protected int|string|array $columnSpan = 'full';

    public function getData(): array
    {
        $stats = PastPaper::select('year', 'season', DB::raw('count(*) as count'))
            ->groupBy('year', 'season')
            ->orderBy('year', 'desc')
            ->get();

        $matrix = [];
        $years = [];
        $seasons = ['spring' => '春', 'autumn' => '秋', 'special' => '特別'];

        foreach ($stats as $stat) {
            $matrix[$stat->year][$stat->season] = $stat->count;
            $years[] = (int) $stat->year;
        }

        // 登録がなくても表示すべき年度（2009年〜現在）を確保
        $minYear = !empty($years) ? min($years) : 2009;
        $maxYear = !empty($years) ? max($years) : date('Y');
        $allYears = range($maxYear, $minYear);

        return [
            'matrix' => $matrix,
            'years' => $allYears,
            'seasons' => $seasons,
            'eraMapper' => fn($y) => $this->getEraName($y),
            'notesMapper' => fn($y, $s = null) => $this->getYearNotes($y, $s),
        ];
    }

    private function getEraName(int $year): string
    {
        if ($year > 2019) {
            return '令和' . ($year - 2018) . '年';
        } elseif ($year === 2019) {
            return '令和元年 / 平成31年';
        } elseif ($year >= 1989) {
            return '平成' . ($year - 1988) . '年';
        }
        return '';
    }

    private function getYearNotes(int $year, ?string $season = null): ?string
    {
        if ($year === 2011 && $season === 'special') {
            return '東日本大震災に伴う特別試験';
        }

        if ($year === 2020 && $season === 'spring') {
            return '中止（新型コロナウイルス）';
        }

        if ($year >= 2023 && $season === 'autumn') {
            return 'シラバス改訂：午後I・IIが統合（全5ファイル）';
        }

        return null;
    }
}
