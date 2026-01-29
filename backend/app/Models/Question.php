<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'filename',
        'data',
        'is_confirmed',
    ];

    protected $casts = [
        'data' => 'array',
        'is_confirmed' => 'boolean',
    ];

    /**
     * PDFファイルとのリレーション (filenameベース)
     */
    public function pdfFile(): BelongsTo
    {
        return $this->belongsTo(PdfFile::class, 'filename', 'filename');
    }
}
