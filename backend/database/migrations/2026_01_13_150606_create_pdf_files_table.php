<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pdf_files', function (Blueprint $table) {
            $table->id();

            // ファイル基本情報
            $table->string('filename');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->unsignedBigInteger('size');

            // 試験メタ情報
            $table->unsignedSmallInteger('year')->index();

            /**
             * spring / autumn
             */
            $table->enum('season', ['spring', 'autumn'])->index();

            /**
             * am2   : 午前Ⅱ
             * pm    : 午後（現行制度）
             * pm1   : 午後Ⅰ（旧制度）
             * pm2   : 午後Ⅱ（旧制度）
             */
            $table->enum('exam_period', ['am2', 'pm', 'pm1', 'pm2'])->index();

            /**
             * question     : 問題
             * answer       : 解答例
             * commentary   : 採点講評・解説
             */
            $table->enum('doc_type', ['question', 'answer', 'commentary'])->index();

            // OpenAI 連携
            $table->string('openai_file_id')->nullable()->index();

            //Vector Store 側で付与される file_id
            $table->string('vector_store_file_id')->nullable();

            /**
             * Vector Store の取り込み状態
             * pending     : 未アップロード（アプリ初期値）
             * in_progress : 処理中（OpenAI API）
             * completed   : 完了（OpenAI API）
             * cancelled   : キャンセル（OpenAI API）
             * failed      : 失敗（OpenAI API）
             * @see https://platform.openai.com/docs/api-reference/vector-stores-files
             */
            $table->string('index_status', 20)->default('pending')->index();

            // completed になった日時
            $table->timestamp('indexed_at')->nullable();

            // OpenAI API の last_error を格納
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_files');
    }
};
