<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_questions テーブルのマイグレーション（既存テーブル対応版）
 * 
 * 注意: このマイグレーションは既存テーブルがある場合は何もしません。
 * テーブルが存在しない場合のみ作成します。
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_questions')) {
            return; // 既存テーブルがあればスキップ
        }

        Schema::create('ai_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question_text');
            $table->json('answer_form_json');
            $table->json('sample_answer_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_questions');
    }
};
