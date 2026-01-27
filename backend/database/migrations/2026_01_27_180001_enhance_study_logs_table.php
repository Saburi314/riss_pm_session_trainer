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
            $table->foreignId('pdf_file_id')->nullable()->after('subcategory_id')->constrained()->nullOnDelete();
            $table->string('exercise_type')->default('ai_generated')->after('pdf_file_id'); // 'past_paper' or 'ai_generated'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->dropForeign(['pdf_file_id']);
            $table->dropColumn(['pdf_file_id', 'exercise_type']);
        });
    }
};
