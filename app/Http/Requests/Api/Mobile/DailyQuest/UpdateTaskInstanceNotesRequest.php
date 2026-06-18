<?php

namespace App\Http\Requests\Api\Mobile\DailyQuest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskInstanceNotesRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
