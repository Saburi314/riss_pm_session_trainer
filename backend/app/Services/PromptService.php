<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Subcategory;
use App\Prompts\RissPrompts;

class PromptService
{

    public function buildGeneratePrompt(?string $category, ?string $subcategory): string
    {
        $context = Category::getCategoryAndSubcategoryNames($category, $subcategory);
        return RissPrompts::getGeneratePrompt($context);
    }

    public function buildScorePrompt(string $exerciseText, string $userAnswer, ?string $category, ?string $subcategory): string
    {
        $context = Category::getCategoryAndSubcategoryNames($category, $subcategory);
        return RissPrompts::getScorePrompt($exerciseText, $userAnswer, $context);
    }
}
