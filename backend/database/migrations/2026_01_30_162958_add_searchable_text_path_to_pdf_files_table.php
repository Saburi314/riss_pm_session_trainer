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
        Schema::table('pdf_files', function (Blueprint $table) {
            // OCR/テキスト抽出後のファイルパス（storage/app からの相対パス）
            $table->string('searchable_text_path')->nullable()->after('storage_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_files', function (Blueprint $table) {
            $table->dropColumn('searchable_text_path');
        });
    }
};
