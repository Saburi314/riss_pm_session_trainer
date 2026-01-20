<?php

namespace App\Http\Requests;

use App\Services\PromptService;
use Illuminate\Foundation\Http\FormRequest;

class GenerateExerciseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'in:' . implode(',', array_keys(PromptService::CATEGORIES))],
            'subcategory' => ['nullable', 'string'],
        ];
    }
}
