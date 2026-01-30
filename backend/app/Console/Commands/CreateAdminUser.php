<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin 
                            {--email= : 管理者のメールアドレス（省略時は.envから取得）}
                            {--password= : 管理者のパスワード（省略時は.envから取得）}
                            {--name= : 管理者の名前（省略時は.envから取得）}
                            {--force : 既存の管理者を上書きする}';

    protected $description = '管理者ユーザーを作成または更新します（.envから認証情報を読み込み）';

    public function handle(): int
    {
        // .env または オプションから値を取得
        $email = $this->option('email') ?? env('ADMIN_EMAIL');
        $password = $this->option('password') ?? env('ADMIN_PASSWORD');
        $name = $this->option('name') ?? env('ADMIN_NAME', 'Administrator');

        // バリデーション
        if (empty($email)) {
            $this->error('管理者のメールアドレスが設定されていません。');
            $this->info('以下のいずれかの方法で設定してください:');
            $this->line('  1. .env に ADMIN_EMAIL を追加');
            $this->line('  2. --email オプションで指定');
            return self::FAILURE;
        }

        if (empty($password)) {
            $this->error('管理者のパスワードが設定されていません。');
            $this->info('以下のいずれかの方法で設定してください:');
            $this->line('  1. .env に ADMIN_PASSWORD を追加');
            $this->line('  2. --password オプションで指定');
            return self::FAILURE;
        }

        // メールアドレスのバリデーション
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('無効なメールアドレスです: ' . $email);
            return self::FAILURE;
        }

        // パスワードの長さチェック
        if (strlen($password) < 8) {
            $this->error('パスワードは8文字以上である必要があります。');
            return self::FAILURE;
        }

        // 既存ユーザーのチェック
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && !$this->option('force')) {
            $this->warn("メールアドレス '{$email}' は既に使用されています。");

            if ($existingUser->isAdmin()) {
                $this->info('このユーザーは既に管理者です。');
            } else {
                $this->info('このユーザーは一般ユーザーです。');
            }

            if (!$this->confirm('上書きしますか？', false)) {
                $this->info('キャンセルしました。');
                return self::SUCCESS;
            }
        }

        // 管理者ユーザーの作成または更新
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(), // 管理者は自動的にメール認証済みにする
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->info("✓ 管理者ユーザーを作成しました");
        } else {
            $this->info("✓ 管理者ユーザーを更新しました");
        }

        $this->newLine();
        $this->line("  名前: {$user->name}");
        $this->line("  メール: {$user->email}");
        $this->line("  権限: {$user->role}");

        return self::SUCCESS;
    }
}
