<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * past_paper_answers テーブルのマイグレーション（既存テーブル対応版）
 * 
 * 注意: このマイグレーションは既存テーブルがある場合は何もしません。
 * テーブルが存在しない場合のみ作成します。
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('past_paper_answers')) {
            return; // 既存テーブルがあればスキップ
        }

        Schema::create('past_paper_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('past_paper_id')->nullable()->constrained('past_papers')->cascadeOnDelete();
            $table->integer('question_number'); // 問1, 問2...
            $table->string('section_id'); // 設問1, 設問2...
            $table->string('item_id'); // a, b, c... or 1, 2, 3...
            $table->text('answer_text'); // 模範解答
            $table->text('explanation')->nullable(); // 解説
            $table->integer('points')->nullable(); // 配点
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('past_paper_answers');
    }
};
