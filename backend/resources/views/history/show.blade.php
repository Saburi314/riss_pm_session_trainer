@extends('layouts.app')

@section('title', '履歴詳細 - RISS対策')

@section('content')
    <div class="card">
        <h2 class="section-header mb-24">
            <span class="indicator {{ $history->exercise_type === 'past_paper' ? 'secondary' : 'primary' }}"></span>
            @if($history->exercise_type === 'past_paper')
                過去問演習詳細: {{ $history->pastPaper ? $history->pastPaper->display_name : '不明' }}
            @else
                AI生成演習詳細: {{ $history->subcategory?->name ?? '全般' }}
            @endif
            <small style="font-weight: 400; font-size: 14px; margin-left: 8px; color: var(--text-muted);">
                ({{ $history->created_at->format('Y/m/d H:i') }})
            </small>
        </h2>

        @if($history->exercise_type === 'past_paper' && $history->pdf_file_id)
            <div class="mb-32">
                <h3 class="section-header mb-16"><span class="indicator primary"></span>演習した試験問題</h3>
                <div class="pdf-container card no-padding">
                    <div id="pdf-loading" class="pdf-loading-overlay">
                        <div class="spinner"></div>
                        <div class="pdf-loading-text">PDFファイルを読み込み中...</div>
                    </div>
                    <iframe src="{{ route('exercise.pdf', $history->pdf_file_id) }}" width="100%" height="100%" frameBorder="0"
                        onload="document.getElementById('pdf-loading').classList.add('hidden')"></iframe>
                </div>
            </div>
        @elseif($history->exercise_text)
            <div class="mb-32">
                <h3 class="section-header mb-16"><span class="indicator primary"></span>問題内容</h3>
                <div class="card" style="background: rgba(255,255,255,0.5);">
                    <div id="exercise-text" class="markdown-body"></div>
                </div>
            </div>
        @endif

        <div class="row">
            @if($history->score !== null)
                <div class="flex-1 text-center mb-32">
                    <div class="score-badge"
                        style="background: {{ $history->score >= 80 ? 'var(--secondary)' : ($history->score >= 60 ? 'var(--primary)' : '#94a3b8') }}">
                        {{ $history->score }}
                    </div>
                    <p class="score-label">獲得スコア</p>
                </div>
            @else
                <div class="flex-1 text-center mb-32">
                    <div class="counter-tag"
                        style="background: #fef3c7; color: #92400e; padding: 12px 24px; border-radius: 20px;">
                        採点未完了
                    </div>
                </div>
            @endif
        </div>

        <div class="row mt-16 pb-24 border-bottom">
            <div class="flex-1 display-flex-wrap-gap-8">
                <button id="btn-retake" class="secondary">この問題に再挑戦する</button>
                <a href="{{ route('history.index') }}" class="nav-brand"
                    style="font-size: 14px; margin-left: 16px; align-self: center;">履歴一覧に戻る</a>
            </div>
        </div>

        <div class="row mt-32">
            <div class="flex-1">
                <h3 class="section-header mb-16"><span class="indicator secondary"></span>提出した解答</h3>
                <div class="card" style="background: rgba(255,255,255,0.5);">
                    <pre style="white-space: pre-wrap; font-family: inherit;">{{ $history->user_answer ?: '解答なし' }}</pre>
                </div>
            </div>
        </div>

        @if($history->feedback)
            <div class="row mt-32">
                <div class="flex-1">
                    <h3 class="section-header mb-16"><span class="indicator primary"></span>AIフィードバック・採点詳細</h3>
                    <div class="card" style="background: rgba(255,255,255,0.5);">
                        <div class="markdown-body" id="feedback-content"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // マークダウン描画
                try {
                    const feedback = @json($history->feedback);
                    const feedbackEl = document.getElementById('feedback-content');
                    if (feedback && feedbackEl) {
                        feedbackEl.innerHTML = marked.parse(feedback);
                    }

                    const exerciseText = @json($history->exercise_text);
                    const exerciseEl = document.getElementById('exercise-text');
                    if (exerciseText && exerciseEl) {
                        exerciseEl.innerHTML = marked.parse(exerciseText);
                    }
                } catch (e) {
                    console.error('Markdown parsing error:', e);
                }

                // 再挑戦ボタン
                const btnRetake = document.getElementById('btn-retake');
                if (btnRetake) {
                    btnRetake.addEventListener('click', function () {
                        const type = "{{ $history->exercise_type }}";
                        const pdfId = "{{ $history->pdf_file_id }}";
                        const logId = "{{ $history->id }}";

                        if (type === 'past_paper' && pdfId) {
                            // 過去問モードでリダイレクト (URLパラメータで指定)
                            window.location.href = `{{ route('exercise.index') }}?mode=past_paper&pdf_id=${pdfId}`;
                        } else {
                            // AI生成問題の場合、DBから取得させるため log_id を渡す
                            window.location.href = `{{ route('exercise.index') }}?mode=ai_generated&retake_log_id=${logId}`;
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection