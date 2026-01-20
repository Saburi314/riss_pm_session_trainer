<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['code', 'name'];

    const DEFAULT_NAME = 'ランダム/全般';
    const NO_SELECTION_REQUIRED_NAME = '選択不要';
    const SELECT_CATEGORY_FIRST_NAME = '(最初にCategoryを選択してください)';

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    /**
     * すべてのカテゴリーコードを取得
     */
    public static function getAllCodes(): array
    {
        return self::pluck('code')->toArray();
    }

    /**
     * フロントエンド用の表示データ構造を取得
     */
    public static function getDisplayData(): array
    {
        $categories = self::with('subcategories')->get();
        $result = [];
        foreach ($categories as $cat) {
            $subcategories = [];
            foreach ($cat->subcategories as $sub) {
                $subcategories[$sub->code] = $sub->name;
            }
            $result[$cat->code] = [
                'category' => $cat->name,
                'subcategories' => $subcategories
            ];
        }
        return $result;
    }

    /**
     * 指定されたコードからカテゴリー名とサブカテゴリー名を取得
     */
    public static function getCategoryAndSubcategoryNames(?string $categoryCode, ?string $subcategoryCode): array
    {
        $categoryLabel = self::DEFAULT_NAME;
        $subcategoryLabel = self::DEFAULT_NAME;

        if ($categoryCode) {
            $category = self::where('code', $categoryCode)->first();
            if ($category) {
                $categoryLabel = $category->name;
                if ($subcategoryCode) {
                    $subcategory = $category->subcategories()->where('code', $subcategoryCode)->first();
                    if ($subcategory) {
                        $subcategoryLabel = $subcategory->name;
                    }
                }
            }
        }

        return ['category' => $categoryLabel, 'subcategory' => $subcategoryLabel];
    }
}
