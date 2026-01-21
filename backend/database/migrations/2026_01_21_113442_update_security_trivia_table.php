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
        Schema::table('security_trivia', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('content')->constrained()->nullOnDelete();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_trivia', function (Blueprint $table) {
            $table->string('category')->index()->after('content');
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
