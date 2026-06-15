<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('posts', 'slug'),
            ],
            'cover' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('uploads.posts.mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.config('uploads.posts.max_size', 2048),
            ],
            'body' => ['required', 'string'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }
}
