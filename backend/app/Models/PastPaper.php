<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\PastPaper
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
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\PastPaperQuestion[] $questions
 */
class PastPaper extends Model
{
    protected $table = 'past_papers';

    protected $fillable = [
        // ファイル基本情報
        'filename',
        'storage_disk',
        'storage_path',
        'searchable_text_path',
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
        return $this->getDisplayName();
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
     * 設問データとのリレーション (IDベース)
     */
    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'past_paper_id');
    }

    /**
     * 模範解答とのリレーション (IDベース)
     */
    public function pastPaperAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PastPaperAnswer::class, 'past_paper_id');
    }

    /**
     * Storage ファイルのフルパスを取得
     */
    public function getFullStoragePath(): string
    {
        return storage_path('app/' . $this->storage_path);
    }

    /**
     * OCR済みファイル（AIが検索可能な形式に変換済みのファイル）のフルパスを取得
     */
    public function getSearchableTextPath(): ?string
    {
        // DBに保存されているパスを優先
        if ($this->searchable_text_path) {
            $path = storage_path('app/' . $this->searchable_text_path);
            return file_exists($path) ? $path : null;
        }

        // 後方互換性: DBにパスがない場合は従来の方法で探す
        $path = storage_path('app/searchable_texts/' . $this->filename . '.txt');
        return file_exists($path) ? $path : null;
    }
}
