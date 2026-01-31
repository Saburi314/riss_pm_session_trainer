<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'past_paper_id',
        'ai_question_id',
        'category_id',
        'subcategory_id',
        'answer_data',
        'score',
        'feedback',
        'exercise_text',
        'user_answer',
        'ai_analysis_data',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'ai_analysis_data' => 'array',
        'score' => 'integer',
    ];

    protected $appends = ['exercise_type'];

    public function getExerciseTypeAttribute(): string
    {
        if ($this->past_paper_id) {
            return 'past_paper';
        }
        if ($this->ai_question_id) {
            return 'ai';
        }
        return 'unknown';
    }

    public function pastPaper(): BelongsTo
    {
        return $this->belongsTo(PastPaper::class, 'past_paper_id');
    }

    public function aiQuestion(): BelongsTo
    {
        return $this->belongsTo(AiQuestion::class, 'ai_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }
}
