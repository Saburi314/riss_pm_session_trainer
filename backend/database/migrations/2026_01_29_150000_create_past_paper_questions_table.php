<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * past_paper_questions テーブルのマイグレーション（既存テーブル対応版）
 * 
 * 注意: このマイグレーションは既存テーブルがある場合は何もしません。
 * テーブルが存在しない場合のみ作成します。
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('past_paper_questions')) {
            return; // 既存テーブルがあればスキップ
        }

        Schema::create('past_paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('past_paper_id')->nullable()->constrained('past_papers')->cascadeOnDelete();
            $table->json('data')->nullable(); // 設問構造 (問・設問・解答群)
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('past_paper_questions');
    }
};
