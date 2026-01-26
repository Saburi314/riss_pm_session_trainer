@extends('layouts.app')

@section('title', 'プロフィール設定 - RISS対策')

@section('content')
    <div class="card" style="max-width: 700px; margin: 0 auto;">
        <h2 class="section-header mb-24">
            <span class="indicator primary"></span>
            プロフィール設定
        </h2>

        <div class="mb-32">
            <h3 class="mb-16" style="font-size: 18px; color: var(--text-main);">ユーザー情報</h3>
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div class="mb-12">
                    <span class="score-label" style="display: inline-block; width: 140px;">お名前:</span>
                    <span style="font-weight: 500;">{{ $user->name }}</span>
                </div>
                <div class="mb-12">
                    <span class="score-label" style="display: inline-block; width: 140px;">メールアドレス:</span>
                    <span>{{ $user->email }}</span>
                </div>
                <div class="mb-0">
                    <span class="score-label" style="display: inline-block; width: 140px;">最終ログイン:</span>
                    <span
                        style="color: var(--text-muted);">{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : '記録なし' }}</span>
                </div>
            </div>
        </div>

        <div class="mb-32">
            <h3 class="mb-16" style="font-size: 18px; color: var(--text-main);">連携済みのソーシャルアカウント</h3>
            @if($user->socialAccounts->count() > 0)
                <div class="display-flex-wrap-gap-8">
                    @foreach($user->socialAccounts as $account)
                        <div
                            style="background: white; padding: 12px 20px; border-radius: 50px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                            @if($account->provider === 'google')
                                <img src="https://www.google.com/favicon.ico" width="16" height="16" alt="Google">
                                <span>Google</span>
                            @elseif($account->provider === 'github')
                                <img src="https://github.com/favicon.ico" width="16" height="16" alt="GitHub">
                                <span>GitHub</span>
                            @else
                                <span>{{ ucfirst($account->provider) }}</span>
                            @endif
                            <span style="color: #10b981; font-size: 12px;">● 連携済み</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">ソーシャル連携はありません。</p>
            @endif
        </div>

        <div class="mt-48" style="padding-top: 32px;">
            <form action="{{ route('profile.withdraw') }}" method="POST"
                onsubmit="return confirm('一度退会すると、これまでの学習履歴はすべて削除され、元に戻すことはできません。本当に退会しますか？');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    style="background: #ef4444; color: white; border: none; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; width: 100%;">
                    退会する（アカウント削除）
                </button>
            </form>
            <p class="mb-24 text-muted" style="font-size: 14px;">
                ※退会すると、これまでの学習履歴や分析結果がすべて参照できなくなります。
            </p>
        </div>
    </div>
@endsection