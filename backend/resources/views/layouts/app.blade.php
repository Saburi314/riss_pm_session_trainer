<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', '情報処理安全確保支援士　午後問対策サイト')</title>
    {{-- 描画ライブラリ --}}
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    @vite(['resources/css/exercise.css', 'resources/js/exercise.js'])
    @stack('styles')
</head>

<body>
    <nav class="navbar">
        <div class="container nav-content">
            <a href="{{ route('exercise.index') }}" class="nav-brand">情報処理安全確保支援士　午後問対策サイト</a>
            <div class="nav-links">
                @auth
                    <a href="{{ route('exercise.index') }}">問題演習</a>
                    <a href="{{ route('history.index') }}">学習履歴</a>
                    <a href="{{ route('analysis') }}">傾向分析</a>
                    <a href="{{ route('profile.show') }}">プロフィール</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="link-button">ログアウト ({{ Auth::user()->name ?? 'User' }})</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">ログイン</a>
                    <a href="{{ route('register') }}">会員登録</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container">
        @if (session('status'))
            <div class="card success-card">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')

</body>

</html>