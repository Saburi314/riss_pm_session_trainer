@extends('layouts.app')

@section('title', '傾向分析 - RISS対策')

@section('content')
    <div class="card">
        <h2 class="section-header mb-24">
            <span class="indicator secondary"></span>
            苦手分野の分析
        </h2>

        @if($analytics->isEmpty())
            <p class="text-center var(--text-muted)">分析を行うのに十分なデータがありません。もっと問題を解いてみましょう！</p>
        @else
            @if($recommendations->isNotEmpty())
                <div class="mb-48">
                    <h3 class="section-header mb-16">
                        <span class="indicator primary"></span>
                        あなたへのおすすめ分野
                    </h3>
                    <div class="display-flex-wrap-gap-16">
                        @foreach($recommendations as $rec)
                            <div class="card flex-1" style="min-width: 250px; border-top: 4px solid var(--primary);">
                                <div class="score-label" style="font-size: 12px;">{{ $rec->category_name }}</div>
                                <h4 class="mb-12" style="font-size: 18px;">{{ $rec->subcategory_name }}</h4>
                                <div class="mb-16">
                                    <span class="status-badge danger">平均 {{ round($rec->average_score, 1) }}点</span>
                                </div>
                                <a href="{{ route('exercise.index', ['category' => $rec->category_code, 'subcategory' => $rec->subcategory_code]) }}"
                                    class="btn-small full-width text-center no-underline">
                                    この分野を特訓する
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="flex-1">
                    <h3 class="section-header mb-16">
                        <span class="indicator secondary"></span>
                        分野別スコア一覧
                    </h3>
                    <p class="mb-16">
                        これまでの学習履歴から、分野別の平均スコアを算出しました。
                        <span class="blank-marker">スコアが低い分野</span>から重点的に復習することをお勧めします。
                    </p>

                    <div class="markdown-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>カテゴリー</th>
                                    <th>サブカテゴリー</th>
                                    <th>平均スコア</th>
                                    <th>実施回数</th>
                                    <th>状況</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analytics as $item)
                                    <tr>
                                        <td>{{ $item->category_name }}</td>
                                        <td>{{ $item->subcategory_name }}</td>
                                        <td>
                                            <div class="progress-container">
                                                <div class="progress-track">
                                                    <div class="progress-bar {{ $item->average_score >= 80 ? 'success' : ($item->average_score >= 60 ? 'warning' : 'danger') }}"
                                                        style="width: {{ $item->average_score }}%;">
                                                    </div>
                                                </div>
                                                <strong>{{ round($item->average_score, 1) }}点</strong>
                                            </div>
                                        </td>
                                        <td>{{ $item->count }}回</td>
                                        <td>
                                            @if($item->average_score >= 80)
                                                <span class="status-badge success">良好</span>
                                            @elseif($item->average_score >= 60)
                                                <span class="status-badge warning">普通</span>
                                            @else
                                                <span class="status-badge danger">苦手</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-32 text-center">
                <a href="{{ route('exercise.index') }}" class="btn-large inline-block no-underline">
                    特訓を開始する
                </a>
            </div>
        @endif
    </div>

@endsection