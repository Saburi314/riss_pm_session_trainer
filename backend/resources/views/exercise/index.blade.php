@extends('layouts.app')

@section('content')
  <h1>{{ config('app.name') }}</h1>

  @if ($errors->any())
    <div class="card error-card">
      <strong class="error-title">入力エラーが発生しました</strong>
      <ul class="error-list mt-12">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="loading-overlay">
    <div class="loading-content">
      <h2 id="loading-title">問題を生成しています</h2>
      <p id="loading-status" class="loading-status">過去問データベースを検索中...</p>

      <div class="loading-timer">
        <span id="timer-count">0</span>秒
      </div>

      <div class="loading-progress-container">
        <div class="loading-progress-track">
          <div id="loading-progress-bar" class="loading-progress-bar"></div>
        </div>
        <div id="loading-progress-percent" class="loading-progress-percent">0%</div>
      </div>

      <div class="trivia-box">
        <div class="trivia-icon">💡</div>
        <div>
          <strong>セキュリティ豆知識</strong>
          <p id="trivia-text">読み込み中...</p>
        </div>
      </div>
    </div>
  </div>

  <section class="card">
    @if($mode === 'past_paper')
      <h2 class="section-header mb-16">過去問を選択</h2>
      <div class="row">
        <div class="flex-1 min-w-150">
          <label class="text-sm font-bold">年度</label>
          <select id="select-year" class="full-width mt-4">
            <option value="" selected disabled>年度を選択</option>
          </select>
        </div>
        <div class="flex-1 min-w-150">
          <label class="text-sm font-bold">時期</label>
          <select id="select-season" class="full-width mt-4" disabled>
            <option value="" selected disabled>時期を選択</option>
          </select>
        </div>
        <div class="flex-1 min-w-150">
          <label class="text-sm font-bold">区分</label>
          <select id="select-period" class="full-width mt-4" disabled>
            <option value="" selected disabled>試験区分を選択</option>
          </select>
        </div>
        <div class="flex-2 min-w-200">
          <label class="text-sm font-bold">&nbsp;</label>
          <button id="btn-load-paper" class="full-width" style="white-space: nowrap;">選択した問題を読み込む</button>
        </div>
      </div>
    @else
      <form id="form-generate" method="post" action="{{ route('exercise.generate') }}">
        @csrf
        <div class="row">
          <div class="flex-2 min-w-200">
            <label class="score-label">Category</label>
            <select name="category" id="category" class="full-width mt-8">
              <option value="">{{ \App\Models\Category::DEFAULT_NAME }}</option>
              @foreach($categories as $code => $cat)
                <option value="{{ $code }}" @selected(($category ?? '') === $code)>{{ $cat['category'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex-2 min-w-200">
            <label class="score-label">Subcategory</label>
            <select name="subcategory" id="subcategory" class="full-width mt-8" disabled>
              <option value="" selected disabled>{{ \App\Models\Category::NO_SELECTION_REQUIRED_NAME }}</option>
            </select>
          </div>
          <div class="flex-1 min-w-150">
            <button type="submit" class="full-width">問題を生成</button>
          </div>
        </div>
      </form>
    @endif
  </section>

  {{-- PDF表示カード --}}
  <article id="pdf-card" class="card hidden no-padding" style="height: 800px;">
    <iframe id="pdf-viewer" src="" width="100%" height="100%" frameborder="0"></iframe>
  </article>

  {{-- 演習問題カード --}}
  <article id="exercise-card" class="card hidden">
    <h2 class="section-header pb-16 mb-24">
      <span class="indicator primary"></span>
      演習問題
    </h2>
    <div id="exercise-content" class="markdown-body"></div>
  </article>

  {{-- 解答カード --}}
  <section id="answer-card" class="card hidden">
    <h2 class="section-header mb-16">
      <span class="indicator secondary"></span>
      解答入力
    </h2>
    <p class="answer-meta mb-24">
      設問番号 (1)〜(5) の形式で解答を記入してください。
    </p>

    <div id="segment-counters" class="mb-16 display-flex-wrap-gap-8"></div>

    <form id="form-score" method="post" action="{{ route('exercise.score') }}">
      @csrf
      <input type="hidden" name="category" value="{{ $category ?? '' }}">
      <input type="hidden" name="subcategory" value="{{ $subcategory ?? '' }}">
      <input type="hidden" name="exercise_text" value="{{ $exerciseText ?? '' }}">
      <input type="hidden" name="pdf_file_id" id="pdf_file_id_hidden">

      <div id="dynamic-form-container" class="mb-24"></div>

      <textarea name="user_answer" id="user_answer"
        placeholder="(1) 解答を入力してください...">{{ $userAnswer ?? "(1)\n(2)\n(3)\n(4)\n(5)" }}</textarea>

      <div class="mt-32 text-center">
        <button type="submit" class="secondary btn-large">
          採点を開始する
        </button>
      </div>
    </form>
  </section>

  {{-- 採点結果カード --}}
  <article id="score-result-card" class="card score-result-card hidden">
    <div class="score-badge-container">
      <div class="score-badge"></div>
    </div>
    <div class="score-label">今回の得点</div>
    <div id="scoring-content" class="markdown-body scoring-content"></div>
  </article>

  <script>
    window.RissApp = {
      mode: "{{ $mode }}",
      categories: @json($categories),
      @if($mode === 'past_paper' && $pastPapers)
        pastPapers: @json($pastPapers),
      @endif
    currentCategory: "{{ $category ?? '' }}",
      currentSubcategory: "{{ $subcategory ?? '' }}",
        exerciseRaw: @json($exerciseText ?? ''),
          scoringRaw: @json($scoringResult ?? ''),
            defaultLabel: "{{ \App\Models\Category::DEFAULT_NAME }}",
              noSelectionLabel: "{{ \App\Models\Category::NO_SELECTION_REQUIRED_NAME }}"
                      };
  </script>
@endsection