<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            // カラムがない場合のみ追加
            if (!Schema::hasColumn('study_logs', 'exercise_text')) {
                $table->text('exercise_text')->nullable()->after('subcategory_id');
            }
            if (!Schema::hasColumn('study_logs', 'user_answer')) {
                $table->text('user_answer')->nullable()->after('exercise_text');
            }
            if (!Schema::hasColumn('study_logs', 'ai_analysis_data')) {
                $table->json('ai_analysis_data')->nullable()->after('answer_data');
            }
        });

        // exercise_type カラムを削除
        if (Schema::hasColumn('study_logs', 'exercise_type')) {
            Schema::table('study_logs', function (Blueprint $table) {
                $table->dropColumn('exercise_type');
            });
        }

        Schema::table('past_paper_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('past_paper_answers', 'ai_draft_generated_at')) {
                $table->timestamp('ai_draft_generated_at')->nullable()->after('data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->dropColumn(['exercise_text', 'user_answer', 'ai_analysis_data']);
            // exercise_type の復元はデータが失われているため完全には不可能だが定義だけ戻す
            if (!Schema::hasColumn('study_logs', 'exercise_type')) {
                $table->string('exercise_type')->default('unknown')->after('subcategory_id');
            }
        });

        Schema::table('past_paper_answers', function (Blueprint $table) {
            $table->dropColumn('ai_draft_generated_at');
        });
    }
};
