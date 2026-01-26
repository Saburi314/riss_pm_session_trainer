@extends('layouts.app')

@section('title', '履歴詳細 - RISS対策')

@section('content')
    <div class="card">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            学習履歴詳細 ({{ $history->created_at->format('Y/m/d H:i') }})
        </h2>

        <div class="mb-24">
            <label class="score-label">カテゴリー / サブカテゴリー</label>
            <p class="mt-8">
                {{ $history->subcategory?->category?->name ?? '不明' }} /
                <strong>{{ $history->subcategory?->name ?? '全般' }}</strong>
            </p>
        </div>

        @if($history->score !== null)
            <div class="text-center mb-32">
                <div class="score-badge"
                    style="background: {{ $history->score >= 60 ? 'var(--secondary)' : 'var(--primary)' }}">
                    {{ $history->score }}
                </div>
                <p class="score-label">獲得スコア</p>
            </div>
        @endif

        <div class="display-flex-wrap-gap-8 mb-32">
            <button id="btn-retake" class="secondary">この問題に再挑戦する</button>
            <a href="{{ route('history.index') }}" class="nav-brand"
                style="font-size: 14px; margin-left: 16px; align-self: center;">履歴一覧に戻る</a>
        </div>

        <div class="row">
            <div class="flex-1">
                <h3 class="section-header mb-16"><span class="indicator primary"></span>問題内容</h3>
                <div class="card" style="background: rgba(255,255,255,0.5);">
                    <div id="exercise-text" class="markdown-body">{!! nl2br(e($history->exercise_text)) !!}</div>
                </div>
            </div>
        </div>

        <div class="row mt-32">
            <div class="flex-1">
                <h3 class="section-header mb-16"><span class="indicator secondary"></span>あなたの解答</h3>
                <div class="card" style="background: rgba(255,255,255,0.5);">
                    <pre style="white-space: pre-wrap;">{{ $history->user_answer }}</pre>
                </div>
            </div>
        </div>

        <div class="row mt-32">
            <div class="flex-1">
                <h3 class="section-header mb-16"><span class="indicator primary"></span>フィードバック</h3>
                <div class="card" style="background: rgba(255,255,255,0.5);">
                    <div class="markdown-body" id="feedback-content"></div>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const feedback = @json($history->feedback);
                const feedbackEl = document.getElementById('feedback-content');
                if (feedback) {
                    feedbackEl.innerHTML = marked.parse(feedback);
                }

                const exerciseText = @json($history->exercise_text);
                const exerciseEl = document.getElementById('exercise-text');
                if (exerciseText) {
                    exerciseEl.innerHTML = marked.parse(exerciseText);
                }

                document.getElementById('btn-retake').addEventListener('click', function () {
                    const exerciseText = @json($history->exercise_text);
                    const category = @json($history->subcategory?->category?->code);
                    const subcategory = @json($history->subcategory?->code);

                    // Redirect to exercise page with data (using sessionStorage for large text)
                    if (window.APP_CONFIG) {
                        sessionStorage.setItem(APP_CONFIG.SESSION_KEYS.RETAKE_EXERCISE, exerciseText);
                        sessionStorage.setItem(APP_CONFIG.SESSION_KEYS.RETAKE_CATEGORY, category);
                        sessionStorage.setItem(APP_CONFIG.SESSION_KEYS.RETAKE_SUBCATEGORY, subcategory);

                        const url = new URL("{{ route('exercise.index') }}", window.location.origin);
                        url.searchParams.set(APP_CONFIG.PARAMS.RETAKE, '1');
                        window.location.href = url.toString();
                    } else {
                        // Fallback if constants are not loaded
                        sessionStorage.setItem('riss_retake_exercise', exerciseText);
                        sessionStorage.setItem('riss_retake_category', category);
                        sessionStorage.setItem('riss_retake_subcategory', subcategory);
                        window.location.href = "{{ route('exercise.index') }}?retake=1";
                    }
                });
            });
        </script>
    @endpush
@endsection