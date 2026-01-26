@extends('layouts.app')

@section('title', '会員登録 - RISS対策')

@section('content')
    <div class="card auth-card">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            会員登録
        </h2>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-16">
                <label class="score-label" for="name">お名前</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus>
                @error('name')
                    <p class="error-list mt-8">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-16">
                <label class="score-label" for="email">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
                @error('email')
                    <p class="error-list mt-8">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-16">
                <label class="score-label" for="password">パスワード</label>
                <input type="password" id="password" name="password" class="form-input" required>
                @error('password')
                    <p class="error-list mt-8">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-24">
                <label class="score-label" for="password_confirmation">パスワード (確認)</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
            </div>

            <div class="text-center">
                <button type="submit" class="full-width btn-large">登録する</button>
            </div>

            <div class="mt-32 text-center">
                <a href="{{ route('login') }}" class="link-secondary">既にアカウントをお持ちの方はこちら</a>
            </div>
        </form>
    </div>
@endsection