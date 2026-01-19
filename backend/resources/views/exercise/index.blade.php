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
      font-size: 1.25em;
      font-weight: 900;
      color: #2d3748;
      display: inline-block;
      margin: 0 2px;
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
          <label class="score-label">大分類</label>
          <select name="major_category" id="major_category" style="width: 100%;">
            <option value="">ランダム/全般</option>
            @foreach(\App\Services\PromptService::CATEGORIES as $key => $cat)
              <option value="{{ $key }}" @selected(($major_category ?? '') === $key)>{{ $cat['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div style="flex: 1;">
          <label class="score-label">小分類</label>
          <select name="minor_category" id="minor_category" style="width: 100%;">
            <option value="">（最初に大分類を選択）</option>
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
      <div id="exercise-content" class="markdown-body">
        <p class="muted">読み込み中...</p>
      </div>
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
        <input type="hidden" name="major_category" value="{{ $major_category ?? '' }}">
        <input type="hidden" name="minor_category" value="{{ $minor_category ?? '' }}">
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
    // 描画ロジック
    const exerciseRaw = @json($exerciseText ?? '');
    const scoringRaw = @json($scoringResult ?? '');

    function preprocessMarkdown(text) {
      if (!text) return '';
      let lines = text.split('\n');
      let result = [];
      let inTable = false;
      for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        let trimmed = line.trim();
        const isTableLine = trimmed.startsWith('|') && (trimmed.match(/\|/g) || []).length >= 2;
        if (isTableLine) {
          if (!inTable) {
            inTable = true;
            result.push(line);
            let nextLine = lines[i + 1] ? lines[i + 1].trim() : '';
            if (nextLine.startsWith('|') && nextLine.includes('---')) {
              result.push(lines[i + 1]);
              i++;
            } else {
              const segments = (line.match(/\|/g) || []).length - 1;
              result.push('|' + Array(Math.max(1, segments)).fill('---|').join(''));
            }
          } else {
            if (trimmed.includes('---') && i > 0 && lines[i - 1].trim().includes('---')) continue;
            result.push(line);
          }
        } else {
          inTable = false;
          result.push(line);
        }
      }
      return result.join('\n');
    }

    function renderMarkdown(raw, targetId) {
      if (!raw) return;
      const target = document.getElementById(targetId);
      if (!target) return;

      const cleanRaw = preprocessMarkdown(raw);

      marked.setOptions({ breaks: true, gfm: true });
      let html = marked.parse(cleanRaw);

      // 穴埋め箇所 ［ a ］ または [ a ] 等を検出してクラスを付与
      html = html.replace(/[［\[]\s*[a-z]\s*[］\]]/g, '<span class="blank-marker">$&</span>');

      const temp = document.createElement('div');
      temp.innerHTML = html;

      const potentialMermaids = temp.querySelectorAll('pre');
      potentialMermaids.forEach(pre => {
        const text = pre.textContent.trim();
        if (/^(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram-v2|stateDiagram|erDiagram|gantt|pie|gitGraph|C4Context|mindmap|timeline)/i.test(text)) {
          const div = document.createElement('div');
          div.className = 'mermaid';
          const decodedText = text
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#39;/g, "'");
          div.textContent = decodedText;
          pre.replaceWith(div);
        }
      });

      target.innerHTML = temp.innerHTML;

      if (window.mermaid) {
        window.mermaid.run({
          nodes: target.querySelectorAll('.mermaid'),
          suppressErrors: true
        }).catch(err => {
          console.error("Mermaid error:", err);
        });
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      renderMarkdown(exerciseRaw, 'exercise-content');
      renderMarkdown(scoringRaw, 'scoring-content');
    });

    const categories = @json(\App\Services\PromptService::CATEGORIES);
    const majorSelect = document.getElementById('major_category');
    const minorSelect = document.getElementById('minor_category');
    const currentMinor = "{{ $minor_category ?? '' }}";

    function updateMinors() {
      if (!majorSelect || !minorSelect) return;
      const selectedMajor = majorSelect.value;

      if (!selectedMajor) {
        minorSelect.innerHTML = '<option value="">（選択不要）</option>';
        minorSelect.disabled = true;
        return;
      }

      minorSelect.disabled = false;
      minorSelect.innerHTML = '<option value="">ランダム/全般</option>';

      if (categories[selectedMajor]) {
        const minors = categories[selectedMajor].minors;
        for (const [key, label] of Object.entries(minors)) {
          const opt = document.createElement('option');
          opt.value = key;
          opt.textContent = label;
          if (key === currentMinor) opt.selected = true;
          minorSelect.appendChild(opt);
        }
      }
    }

    if (majorSelect) majorSelect.addEventListener('change', updateMinors);
    updateMinors();

    // 文字数カウント
    const answerArea = document.getElementById('user_answer');
    const countBox = document.getElementById('segment-counters');

    if (answerArea) {
      answerArea.addEventListener('input', () => {
        const val = answerArea.value;
        const matches = [...val.matchAll(/\((\d+)\)/g)];
        if (!countBox) return;
        countBox.innerHTML = '';

        if (matches.length === 0 && val.length > 0) {
          countBox.innerHTML = `<span class="counter-tag">全体: ${val.length} 文字</span>`;
        } else {
          matches.forEach((m, i) => {
            const start = m.index + m[0].length;
            const end = matches[i + 1] ? matches[i + 1].index : val.length;
            const len = val.substring(start, end).trim().length;
            countBox.innerHTML += `<span class="counter-tag">(${m[1]}): ${len} 文字</span>`;
          });
        }
      });
      answerArea.dispatchEvent(new Event('input'));
    }
  </script>
</body>

</html>