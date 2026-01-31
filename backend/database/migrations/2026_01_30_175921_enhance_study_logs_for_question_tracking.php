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
            $table->integer('question_number')->nullable()->after('pdf_file_id');
            $table->json('answer_details')->nullable()->after('user_answer');
            // answer_details の構造例:
            // {
            //   "1": {"a": "リスク分析", "b": "脆弱性"},
            //   "2": {"1": "定期的な監査を実施するため"}
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->dropColumn(['question_number', 'answer_details']);
        });
    }
};
