<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['category_id', 'code', 'name'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * すべてのサブカテゴリーコードを取得（特定カテゴリー限定、または全件）
     */
    public static function getAllCodes(?string $categoryCode = null): array
    {
        if ($categoryCode) {
            $category = Category::where('code', $categoryCode)->first();
            return $category ? $category->subcategories()->pluck('code')->toArray() : [];
        }

        return self::pluck('code')->distinct()->toArray();
    }
}
