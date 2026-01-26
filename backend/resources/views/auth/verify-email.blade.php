@extends('layouts.app')

@section('title', 'メールアドレス確認 - RISS対策')

@section('content')
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            メールアドレスの確認
        </h2>

        <p class="mb-24">
            ご登録ありがとうございます。<br>
            登録されたメールアドレスに認証用リンクをお送りしました。メール内のリンクをクリックして、メールアドレスの認証を行ってください。
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="success-card mb-24">
                新しい認証リンクを送信しました。メールをご確認ください。
            </div>
        @endif

        <div class="display-flex-wrap-gap-8 text-center" style="justify-content: center;">
            <p class="mb-16" style="width: 100%; color: var(--text-muted); font-size: 14px;">
                メールが届かない場合は、以下のボタンから再送することができます。
            </p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                @if(Auth::check())
                    <input type="hidden" name="email" value="{{ Auth::user()->email }}">
                @else
                    <div class="mb-16">
                        <input type="email" name="email" class="form-input" placeholder="メールアドレスを入力" required
                            style="max-width: 300px; margin: 0 auto;">
                    </div>
                @endif
                <button type="submit" class="secondary btn-large">認証メールを再送する</button>
            </form>
        </div>

        <div class="mt-32 text-center" style="border-top: 1px solid #e2e8f0; padding-top: 24px;">
            <p class="text-muted" style="font-size: 14px;">
                ※メールが届かない場合は、迷惑メールフォルダもご確認ください。
            </p>
        </div>

    </div>
@endsection