<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfFile extends Model
{
    protected $fillable = [
        // ファイル基本情報
        'filename',
        'storage_disk',
        'storage_path',
        'size',

        // 試験メタ情報
        'year',
        'season',
        'exam_period',
        'doc_type',

        // OpenAI連携
        'openai_file_id',
        'vector_store_file_id',
        'index_status',
        'indexed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'size' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    /**
     * 未アップロードのPDFを取得
     */
    public function scopePending($query)
    {
        return $query->where('index_status', 'pending');
    }

    /**
     * Storage ファイルのフルパスを取得
     */
    public function getFullStoragePath(): string
    {
        return storage_path('app/' . $this->storage_path);
    }
}
