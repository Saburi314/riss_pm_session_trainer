<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityTrivia extends Model
{
    use HasFactory;

    protected $table = 'security_trivia';

    protected $fillable = [
        'content',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * カテゴリに基づいてランダムなトリビアを取得
     */
    public static function getRandomListByCategory(?string $categoryCode, int $limit = 10)
    {
        $query = self::query();

        if ($categoryCode) {
            $category = Category::where('code', $categoryCode)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        return $query->inRandomOrder()->limit($limit)->get();
    }
}
