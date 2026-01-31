<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'question_text',
        'answer_form_json',
        'sample_answer_json',
    ];

    protected $casts = [
        'answer_form_json' => 'array',
        'sample_answer_json' => 'array',
    ];

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
