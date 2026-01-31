@extends('layouts.app')

@section('title', '学習履歴 - RISS対策')

@section('content')
    <div class="card">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            学習履歴
        </h2>

        @if($logs->isEmpty())
            <p class="text-center var(--text-muted)">まだ学習履歴がありません。問題を解いてみましょう！</p>
        @else
            <div class="markdown-body">
                <table>
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>カテゴリー / サブカテゴリー</th>
                            <th>スコア</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y/m/d H:i') }}</td>
                                <td>
                                    @if($log->exercise_type === 'past_paper')
                                        <span class="counter-tag"
                                            style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">過去問演習</span>
                                        {{ $log->pastPaper ? $log->pastPaper->display_name : '過去問演習' }}
                                    @else
                                        <span class="counter-tag"
                                            style="background: #f0fdf4; color: #166534; border-color: #bbf7d0;">AI演習</span>
                                        {{ $log->subcategory?->category?->name ?? 'ランダム' }} /
                                        <span class="text-muted">{{ $log->subcategory?->name ?? '全般' }}</span>
                                    @endif
                                </td>
                                <td style="white-space: nowrap;">
                                    @if($log->score !== null)
                                        <span class="blank-marker">{{ $log->score }}点</span>
                                    @else
                                        <span style="color: #f59e0b; font-weight: 600;">採点待ち</span>
                                    @endif
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="{{ route('history.show', $log) }}" class="nav-brand"
                                        style="font-size: 14px;">詳細・再挑戦</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-24">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection