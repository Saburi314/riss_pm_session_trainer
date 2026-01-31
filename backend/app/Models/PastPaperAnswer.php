<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastPaperAnswer extends Model
{
    protected $table = 'past_paper_answers';

    protected $fillable = [
        'past_paper_id',
        'data',
        'ai_draft_generated_at',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * PDFファイルとのリレーション (IDベース)
     */
    public function pastPaper(): BelongsTo
    {
        return $this->belongsTo(PastPaper::class, 'past_paper_id');
    }
}
