<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RISS AIトレーナー</title>
  {{-- 描画ライブラリ --}}
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script type="module">
    import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
    mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose' });
    window.mermaid = mermaid;
  </script>
  <style>
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      margin: 0;
      padding: 40px;
      background-color: #f4f7f6;
      color: #333;
    }

    h1 {
      color: #1a202c;
      font-weight: 800;
      margin-bottom: 30px;
      text-align: center;
    }

    .card {
      background: #ffffff;
      border: none;
      padding: 24px;
      border-radius: 16px;
      margin-bottom: 24px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    textarea {
      width: 100%;
      min-height: 200px;
      padding: 16px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      line-height: 1.6;
      resize: vertical;
    }

    .row {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    button {
      background: #4a90e2;
      color: white;
      font-weight: 600;
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      box-shadow: 0 4px 14px 0 rgba(74, 144, 226, 0.39);
    }

    .score-badge {
      display: inline-block;
      width: 100px;
      height: 100px;
      line-height: 100px;
      border-radius: 50%;
      text-align: center;
      font-size: 36px;
      font-weight: 900;
      background: #4a90e2;
      color: white;
      margin: 10px auto;
    }

    .score-label {
      font-size: 12px;
      text-transform: uppercase;
      color: #718096;
      font-weight: bold;
    }

    .counter-tag {
      display: inline-block;
      background: #ebf4ff;
      color: #2b6cb0;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      margin-right: 8px;
      margin-bottom: 8px;
    }

    /* Markdown Table & Code Styles */
    .markdown-body {
      line-height: 1.8;
      font-size: 16px;
    }

    .markdown-body table {
      border-collapse: collapse;
      width: 100%;
      margin: 24px 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      font-size: 14px;
      background: white;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      table-layout: auto;
      display: block;
      overflow-x: auto;
    }

    .markdown-body th,
    .markdown-body td {
      border: 1px solid #cbd5e0;
      padding: 12px 16px;
      text-align: left;
      min-width: 120px;
    }

    .markdown-body th {
      background-color: #f8fafc;
      font-weight: 700;
      color: #1e293b;
    }

    .markdown-body pre {
      background: #1e293b;
      color: #f8fafc;
      padding: 20px;
      border-radius: 12px;
      overflow-x: auto;
      margin: 20px 0;
      box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
      border: 1px solid #334155;
    }

    .markdown-body code {
      font-family: 'JetBrains Mono', 'Fira Code', monospace;
      font-size: 0.9em;
      padding: 0.2em 0.4em;
      background: #f1f5f9;
      border-radius: 4px;
      color: #0f172a;
    }

    .markdown-body pre code {
      background: transparent;
      padding: 0;
      color: inherit;
      font-size: 14px;
      line-height: 1.6;
    }

    .mermaid {
      background: white;
      padding: 24px;
      border-radius: 12px;
      margin: 12px 0 24px 0;
      text-align: center;
      border: 1px solid #e2e8f0;
    }


    pre {
      white-space: pre-wrap;
      background: #f8fafc;
      padding: 20px;
      border-radius: 12px;
      border: 1px solid #edf2f7;
    }

    select {
      background: white;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      padding: 10px;
    }

    .blank-marker {
      font-size: 1.1em;
      font-weight: 700;
      display: inline-block;
      margin: 0 4px;
    }

    .markdown-body h1,
    .markdown-body h2,
    .markdown-body h3 {
      margin-top: 1.5em;
      margin-bottom: 1em;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 0.5em;
    }
  </style>
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
    const categories = @json(\App\Services\PromptService::CATEGORIES);
    const categorySelect = document.getElementById('category');
    const subcategorySelect = document.getElementById('subcategory');
    const currentSubcategory = "{{ $subcategory ?? '' }}";

    // 小分類の選択肢を同期する（Categoryを選択するとSubcategoryの選択肢が更新される）
    function syncSubcategoryList() {
      if (!categorySelect || !subcategorySelect) return;
      const selectedCategory = categorySelect.value;

      if (!selectedCategory) {
        const opt = document.createElement('option');
        opt.value = "";
        opt.textContent = "（選択不要）";
        subcategorySelect.replaceChildren(opt);
        subcategorySelect.disabled = true;
        return;
      }

      subcategorySelect.disabled = false;
      const defaultOpt = document.createElement('option');
      defaultOpt.value = "";
      defaultOpt.textContent = "ランダム/全般";
      subcategorySelect.replaceChildren(defaultOpt);

      if (categories[selectedCategory]) {
        const subcategories = categories[selectedCategory].subcategories;
        for (const [key, label] of Object.entries(subcategories)) {
          const opt = document.createElement('option');
          opt.value = key;
          opt.textContent = label;
          if (key === currentSubcategory) opt.selected = true;
          subcategorySelect.appendChild(opt);
        }
      }
    }

    if (categorySelect) categorySelect.addEventListener('change', syncSubcategoryList);
    syncSubcategoryList();

    const answerArea = document.getElementById('user_answer');
    const countBox = document.getElementById('segment-counters');

    // 文字数カウント（設問4のみ文字数をカウント）
    function refreshCharacterCounter() {
      if (!answerArea || !countBox) return;
      const val = answerArea.value;
      const matches = [...val.matchAll(/\((\d+)\)/g)];
      countBox.textContent = '';

      matches.forEach((m, i) => {
        const num = m[1];
        // 設問4のみ文字数をカウント
        if (num === '4') {
          const start = m.index + m[0].length;
          const end = matches[i + 1] ? matches[i + 1].index : val.length;
          const len = val.substring(start, end).trim().length;

          const span = document.createElement('span');
          span.className = 'counter-tag';
          span.textContent = `(${num}): ${len} 文字`;
          countBox.appendChild(span);
        }
      });
    }

    if (answerArea) {
      answerArea.addEventListener('input', refreshCharacterCounter);
      refreshCharacterCounter();
    }

    // AIの書き漏らしを補完するセーフティネット
    function robustPreprocess(text) {
      if (!text) return '';
      // ヘッダー直後に改行がない場合の補完（空行を確保して見やすくする）
      text = text.replace(/(【問題文】|【設問】)([^\s\n])/g, '$1\n\n$2');

      let lines = text.split('\n');
      let result = [];

      for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        let trimmed = line.trim();

        // テーブル行のクリーニング
        if (trimmed.startsWith('|')) {
          const content = trimmed.replace(/[\||\s|.|-]/g, '');
          if (content === '' && !trimmed.includes('---|') && !trimmed.includes('---')) {
            continue;
          }
        }


        result.push(line);
      }
      return result.join('\n');
    }

    // コンテンツの表示初期化
    function initializeDisplay() {
      const exerciseRaw = @json($exerciseText ?? '');
      const scoringRaw = @json($scoringResult ?? '');

      const exerciseContent = document.getElementById('exercise-content');
      const scoringContent = document.getElementById('scoring-content');

      if (exerciseContent && exerciseRaw) {
        // カスタムレンダラー（Mermaid対応）
        const renderer = new marked.Renderer();
        // Marked v4+ の引数仕様に合わせる
        renderer.code = function (args) {
          const text = typeof args === 'string' ? args : args.text;
          const language = typeof args === 'string' ? arguments[1] : args.lang;

          if (language === 'mermaid') {
            return `<div class="mermaid">${text}</div>`;
          }
          return `<pre><code class="language-${language}">${text}</code></pre>`;
        };

        // セーフティネットを通してパース
        const robustText = robustPreprocess(exerciseRaw);
        // [a] の強調（太字があってもなくても対応）
        const highlighted = robustText.replace(/(\*\*)?［\s*([a-z])\s*］(\*\*)?/g, '<span class="blank-marker">［ $2 ］</span>');
        exerciseContent.innerHTML = marked.parse(highlighted, {
          renderer: renderer,
          breaks: true // GFM-like line breaks
        });
      }

      if (scoringContent && scoringRaw) {
        scoringContent.innerHTML = marked.parse(scoringRaw, { breaks: true });
      }

      // Mermaid図の描画
      if (typeof mermaid !== 'undefined') {
        setTimeout(() => {
          mermaid.run({
            querySelector: '.mermaid'
          }).catch(err => console.error('Mermaid error:', err));
        }, 100);
      }
    }

    document.addEventListener('DOMContentLoaded', initializeDisplay);
  </script>
</body>

</html>