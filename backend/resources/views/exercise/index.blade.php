<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RISS AIトレーナー</title>
  {{-- 描画ライブラリ --}}
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

  @vite(['resources/css/exercise.css', 'resources/js/exercise.js'])
</head>

<body>
  <h1>RISS AIトレーナー</h1>

  @if ($errors->any())
    <div class="card" style="border-left: 5px solid #e53e3e;">
      <strong style="color: #e53e3e;">入力エラー</strong>
      <ul style="margin-top: 8px;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <form method="post" action="{{ route('exercise.generate') }}">
      @csrf
      <div class="row">
        <div style="flex: 1;">
          <label class="score-label">Category</label>
          <select name="category" id="category" style="width: 100%;">
            <option value="">ランダム/全般</option>
            @foreach(\App\Services\PromptService::CATEGORIES as $key => $cat)
              <option value="{{ $key }}" @selected(($category ?? '') === $key)>{{ $cat['category'] }}</option>
            @endforeach
          </select>
        </div>
        <div style="flex: 1;">
          <label class="score-label">Subcategory</label>
          <select name="subcategory" id="subcategory" style="width: 100%;">
            <option value="">（最初にCategoryを選択してください）</option>
          </select>
        </div>
        <button type="submit">問題を生成する</button>
      </div>
    </form>
  </div>

  @isset($exerciseText)
    <div class="card">
      <h2
        style="display: flex; align-items: center; border-bottom: 2px solid #edf2f7; padding-bottom: 12px; font-size: 20px;">
        <span style="background: #4a90e2; width: 8px; height: 24px; border-radius: 4px; margin-right: 12px;"></span>
        演習問題
      </h2>
      <div id="exercise-content" class="markdown-body"></div>
    </div>

    <div class="card">
      <h2 style="display: flex; align-items: center; font-size: 20px;">
        <span style="background: #48bb78; width: 8px; height: 24px; border-radius: 4px; margin-right: 12px;"></span>
        解答
      </h2>
      <p style="color: #666; font-size: 14px; margin-bottom: 16px;">
        ※ 設問番号 <strong>(1)</strong> などの記号の後に解答を記入してください。
      </p>

      <div id="segment-counters" style="margin-bottom: 12px; min-height: 40px;"></div>

      <form method="post" action="{{ route('exercise.score') }}">
        @csrf
        <input type="hidden" name="category" value="{{ $category ?? '' }}">
        <input type="hidden" name="subcategory" value="{{ $subcategory ?? '' }}">
        <input type="hidden" name="exercise_text" value="{{ $exerciseText }}">

        <textarea name="user_answer" id="user_answer"
          placeholder="(1) 解答を入力...">{{ $userAnswer ?? "(1)\n(2)\n(3)\n(4)\n(5)" }}</textarea>

        <div style="margin-top: 24px; text-align: center;">
          <button type="submit"
            style="background: #48bb78; padding: 16px 48px; font-size: 18px; box-shadow: 0 4px 14px 0 rgba(72, 187, 120, 0.4);">
            採点する
          </button>
        </div>
      </form>
    </div>
  @endisset

  @isset($scoringResult)
    <div class="card" style="text-align: center; border-top: 8px solid #4a90e2; padding-top: 40px;">
      @php
        preg_match('/点数：(\d+)/', $scoringResult, $matches);
        $scoreValue = (int) ($matches[1] ?? 0);
        $scoreColor = $scoreValue >= 80 ? '#48bb78' : ($scoreValue >= 60 ? '#ecc94b' : '#f6ad55');
      @endphp

      <div class="score-badge" style="background: {{ $scoreColor }}; box-shadow: 0 10px 25px {{ $scoreColor }}66;">
        {{ $scoreValue }}
      </div>
      <div class="score-label" style="font-weight: 800; transform: translateY(-10px); color: {{ $scoreColor }};">Result
        Score</div>

      <div id="scoring-content" class="markdown-body" style="text-align: left; margin-top: 40px;">
      </div>
    </div>
  @endisset

  <script>
    window.RissApp = {
      categories: @json(\App\Services\PromptService::CATEGORIES),
      currentSubcategory: "{{ $subcategory ?? '' }}",
      exerciseRaw: @json($exerciseText ?? ''),
      scoringRaw: @json($scoringResult ?? '')
    };
  </script>
</body>

</html>