<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PdfFile
 *
 * @property int $id
 * @property string $filename
 * @property string $storage_disk
 * @property string $storage_path
 * @property int $size
 * @property int $year
 * @property string $season
 * @property string $exam_period
 * @property string $doc_type
 * @property string|null $openai_file_id
 * @property string|null $vector_store_file_id
 * @property Question|null $question
 */
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
     * 表示用の名称を取得 (例: 2024年 (令和6年) 春期 午後　午後１)
     */
    public function getDisplayNameAttribute(): string
    {
        $gengo = $this->getYearGengo();
        $season = $this->getSeasonName();
        $period = $this->getPeriodName();

        return "{$this->year}年 ({$gengo}) {$season} {$period}";
    }

    public function getYearGengo(): string
    {
        if ($this->year >= 2019) {
            $r = $this->year - 2018;
            return "令和" . ($r === 1 ? '元' : $r) . "年";
        } elseif ($this->year >= 1989) {
            $h = $this->year - 1988;
            return "平成" . ($h === 1 ? '元' : $h) . "年";
        }
        return "西暦{$this->year}年";
    }

    public function getSeasonName(): string
    {
        return match ($this->season) {
            'spring' => '春期',
            'autumn' => '秋期',
            'special' => '特別',
            default => $this->season,
        };
    }

    public function getPeriodName(): string
    {
        return match (strtolower($this->exam_period)) {
            'pm' => '午後',
            'pm1' => '午後Ⅰ',
            'pm2' => '午後Ⅱ',
            default => strtoupper($this->exam_period),
        };
    }

    public function getDisplayName(): string
    {
        return "{$this->year}年 ({$this->getYearGengo()}) {$this->getSeasonName()} {$this->getPeriodName()}";
    }

    /**
     * 設問データとのリレーション (filenameベース)
     */
    public function question(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Question::class, 'filename', 'filename');
    }

    /**
     * Storage ファイルのフルパスを取得
     */
    public function getFullStoragePath(): string
    {
        return storage_path('app/' . $this->storage_path);
    }
}
