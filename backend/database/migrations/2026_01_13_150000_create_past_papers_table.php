<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * past_papers テーブルのマイグレーション（既存テーブル対応版）
 * 
 * 注意: このマイグレーションは既存テーブルがある場合は何もしません。
 * テーブルが存在しない場合のみ作成します。
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('past_papers')) {
            return; // 既存テーブルがあればスキップ
        }

        Schema::create('past_papers', function (Blueprint $table) {
            $table->id();

            // ファイル基本情報
            $table->string('filename');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('searchable_text_path')->nullable();
            $table->bigInteger('size')->unsigned();

            // 試験メタ情報
            $table->smallInteger('year')->unsigned();
            $table->enum('season', ['spring', 'autumn', 'special']);
            $table->enum('exam_period', ['PM', 'PM1', 'PM2']);
            $table->enum('doc_type', ['question_booklet', 'answer_sheet']);

            // OpenAI連携
            $table->string('openai_file_id')->nullable();
            $table->string('vector_store_file_id')->nullable();
            $table->string('index_status', 20)->default('pending');
            $table->timestamp('indexed_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // インデックス
            $table->index('year');
            $table->index('season');
            $table->index('exam_period');
            $table->index('doc_type');
            $table->index('openai_file_id');
            $table->index('index_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('past_papers');
    }
};
