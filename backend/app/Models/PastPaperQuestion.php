<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastPaperQuestion extends Model
{
    protected $table = 'past_paper_questions';

    protected $fillable = [
        'past_paper_id',
        'data',
        'is_confirmed',
    ];

    protected $casts = [
        'data' => 'array',
        'is_confirmed' => 'boolean',
    ];

    /**
     * PDFファイルとのリレーション (IDベース)
     */
    public function pastPaper(): BelongsTo
    {
        return $this->belongsTo(PastPaper::class, 'past_paper_id');
    }

    /**
     * 模範解答とのリレーション (IDベース)
     */
    public function pastPaperAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PastPaperAnswer::class, 'past_paper_id', 'past_paper_id');
    }
}
