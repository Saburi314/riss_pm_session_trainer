@extends('layouts.app')

@section('container_class', 'container-' . $mode)

@push('styles')
  <style>
    /* Width by Mode */
    body .container.container-past_paper {
      max-width: 2000px !important;
      width: 98% !important;
    }

    body .container.container-ai_generated {
      max-width: 1400px !important;
      width: 95% !important;
    }

    body .container {
      transition: none !important;
    }

    /* Layout for Past Paper (PDF) */
    .container-past_paper .exercise-split-container {
      display: flex !important;
      gap: 32px !important;
      align-items: stretch !important;
      height: 90vh !important;
      min-height: 600px !important;
    }

    .container-past_paper .exercise-split-left,
    .container-past_paper .exercise-split-right {
      display: flex !important;
      flex-direction: column !important;
      height: 100% !important;
      flex: 1 !important;
      /* Force 50/50 split */
    }

    .container-past_paper .exercise-split-left .card,
    .container-past_paper .exercise-split-right .card {
      height: 100% !important;
      overflow-y: auto !important;
    }

    /* Layout for AI Generated (No forced height) */
    .container-ai_generated .exercise-split-container {
      display: flex !important;
      gap: 32px !important;
      align-items: flex-start !important;
      height: auto !important;
    }

    .container-ai_generated .exercise-split-left,
    .container-ai_generated .exercise-split-right {
      height: auto !important;
    }

    .container-ai_generated .exercise-split-left .card,
    .container-ai_generated .exercise-split-right .card {
      height: auto !important;
      overflow: visible !important;
    }

    /* Shared Card Styles */
    .exercise-split-left .card,
    .exercise-split-right .card {
      background-color: #ffffff !important;
      border: none !important;
      box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
    }

    .pdf-container-inner {
      background-color: #f1f5f9 !important;
      padding: 0 !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      /* Center PDF pages */
    }

    .pdf-container-inner canvas {
      margin-bottom: 24px !important;
      /* Gap between pages */
      box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important;
    }

    .no-padding {
      padding: 0 !important;
    }

    .paper-mode {
      padding: 40px !important;
    }

    /* AI Generated Spacing */
    .markdown-body p {
      margin-bottom: 2rem !important;
      line-height: 1.8 !important;
    }

    /* Choice text wrapping */
    .answer-item label span,
    .cursor-pointer span {
      white-space: normal !important;
      display: inline-block !important;
      flex: 1 !important;
    }

    /* Tabs UI */
    .question-tabs {
      display: flex !important;
      background: #f8fafc !important;
      border-bottom: 2px solid #e2e8f0 !important;
      padding: 0 20px !important;
      gap: 4px !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 50 !important;
    }

    .question-tab {
      padding: 12px 24px !important;
      border: none !important;
      background: none !important;
      font-weight: 700 !important;
      color: #64748b !important;
      cursor: pointer !important;
      border-bottom: 3px solid transparent !important;
      transition: all 0.2s !important;
      border-radius: 0 !important;
      box-shadow: none !important;
    }

    .question-tab:hover {
      background: #f1f5f9 !important;
      color: #1e293b !important;
      transform: none !important;
      box-shadow: none !important;
    }

    .question-tab.active {
      color: #4f46e5 !important;
      border-bottom-color: #4f46e5 !important;
      background: white !important;
    }

    .tab-content {
      padding: 40px !important;
      background: white !important;
    }

    .tab-pane {
      display: none;
    }

    .tab-pane.active {
      display: block !important;
    }
  </style>
@endpush

@push('scripts')
  {{-- PDF.js for reliable embedding --}}
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  </script>
@endpush

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

  {{-- 問題選択カード --}}
  <div id="question-selector-container" class="hidden"></div>

  {{-- Split Layout Container --}}
  <div class="exercise-split-container hidden" id="exercise-split-container">
    {{-- Left Side: Problem --}}
    <div class="exercise-split-left">
      {{-- PDF表示カード --}}
      <article id="pdf-card" class="card hidden no-padding" style="position: relative;">
        <div id="pdf-viewer-container" class="pdf-container-inner" style="width: 100%;"></div>
      </article>

      {{-- 演習問題カード --}}
      <article id="exercise-card" class="card paper-mode hidden">
        <h2 class="section-header pb-16 mb-24">
          <span class="indicator primary"></span>
          演習問題
        </h2>
        <div id="exercise-content" class="markdown-body"></div>
      </article>
    </div>

    {{-- Right Side: Answer --}}
    <div class="exercise-split-right">
      {{-- 解答カード (タブ形式) --}}
      <article id="answer-card" class="card hidden no-padding"
        style="display: flex; flex-direction: column; overflow: hidden;">
        <div id="past-paper-tabs" class="question-tabs"></div>

        <div class="paper-mode" style="flex: 1; overflow-y: auto;">
          <form id="form-score" method="post" action="{{ route('exercise.score') }}">
            @csrf
            <input type="hidden" name="category" value="{{ $category ?? '' }}">
            <input type="hidden" name="subcategory" value="{{ $subcategory ?? '' }}">
            <input type="hidden" name="exercise_text" value="{{ $exerciseText ?? '' }}">
            <input type="hidden" name="past_paper_id" id="past_paper_id_hidden">

            <div id="dynamic-form-container" class="mb-24"></div>

            <textarea name="user_answer" id="user_answer" class="hidden"
              placeholder="(1) 解答を入力してください...">{{ $userAnswer ?? "(1)\n(2)\n(3)" }}</textarea>

            <div class="mt-32 text-center">
              <button type="submit" class="secondary btn-large">
                採点を開始する
              </button>
            </div>
          </form>
        </div>
      </article>
    </div>
  </div>

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