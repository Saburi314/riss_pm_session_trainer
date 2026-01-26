@extends('layouts.app')

@section('title', '認証メール再送 - RISS対策')

@section('content')
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            認証メールの再送
        </h2>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div class="mb-24">
                <label class="score-label" for="email">登録メールアドレス</label>
                <input type="email" id="email" name="email" class="full-width mt-8" value="{{ old('email') }}" required
                    autofocus style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px;">
                @error('email')
                    <p class="error-list mt-8">{{ $message }}</p>
                @enderror
            </div>

            <div class="text-center">
                <button type="submit" class="full-width btn-large">再送する</button>
            </div>

            <div class="mt-32 text-center">
                <a href="{{ route('login') }}"
                    style="color: var(--text-muted); font-size: 14px; text-decoration: none;">ログイン画面に戻る</a>
            </div>
        </form>
    </div>
@endsection