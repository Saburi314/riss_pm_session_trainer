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
            $table->json('answer_form_json')->nullable()->after('doc_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_files', function (Blueprint $table) {
            $table->dropColumn('answer_form_json');
        });
    }
};
