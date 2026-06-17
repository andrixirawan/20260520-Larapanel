<?php

namespace App\Http\Requests\DailyQuest;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:24'],
            'icon' => ['nullable', 'string', 'max:64'],
        ];
    }
}
