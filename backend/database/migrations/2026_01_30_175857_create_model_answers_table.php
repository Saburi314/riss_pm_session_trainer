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
        Schema::create('sample_answers', function (Blueprint $table) {
            $table->id();
            $table->string('filename'); // questions.filename と紐付け
            $table->integer('question_number'); // 問1, 問2...
            $table->string('section_id'); // 設問1, 設問2...
            $table->string('item_id'); // a, b, c... or 1, 2, 3...
            $table->text('answer_text'); // 模範解答
            $table->text('explanation')->nullable(); // 解説
            $table->integer('points')->nullable(); // 配点
            $table->timestamps();

            // 複合ユニークキー
            $table->unique(['filename', 'question_number', 'section_id', 'item_id'], 'sample_answers_unique');

            // 外部キー
            $table->foreign('filename')
                ->references('filename')
                ->on('questions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_answers');
    }
};
