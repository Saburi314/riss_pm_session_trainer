<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 管理者アカウントの作成/更新（.envから読み込み）
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');
        $adminName = env('ADMIN_NAME', 'Admin User');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => bcrypt($adminPassword),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // テストユーザーの作成/更新（開発環境のみ）
        if (app()->environment('local')) {
            User::updateOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => bcrypt('password'),
                    'role' => 'user',
                ]
            );
        }

        $this->call([
            CategorySeeder::class,
            SecurityTriviaSeeder::class,
        ]);
    }
}
