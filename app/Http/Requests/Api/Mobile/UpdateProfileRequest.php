<?php

namespace App\Http\Requests\Api\Mobile;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'avatar' => $this->avatarRules(),
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
