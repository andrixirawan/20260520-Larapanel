<?php

namespace App\Http\Requests\DailyQuest;

use App\Models\DailyQuest\TaskCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
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
            'category_public_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:24'],
            'points' => ['required', 'integer', 'min:0', 'max:100000'],
            'recurrence_type' => ['required', 'string', 'in:daily,specific_days,one_time,x_days,date_range'],
            'recurrence_days' => ['nullable', 'array'],
            'recurrence_days.*' => ['string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'recurrence_starts_at' => ['nullable', 'date'],
            'recurrence_ends_at' => ['nullable', 'date', 'after_or_equal:recurrence_starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateCategoryOwnership($validator);
            $this->validateRecurrenceFields($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function taskAttributes(): array
    {
        $validated = $this->validated();
        $category = $this->resolvedCategory();

        $validated['category_id'] = $category?->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        if (($validated['recurrence_type'] ?? null) !== 'specific_days') {
            $validated['recurrence_days'] = null;
        }

        if (! in_array($validated['recurrence_type'] ?? null, ['one_time', 'date_range'], true)) {
            $validated['recurrence_starts_at'] = $validated['recurrence_starts_at'] ?? null;
        }

        if (! in_array($validated['recurrence_type'] ?? null, ['x_days', 'date_range'], true)) {
            $validated['recurrence_ends_at'] = null;
        }

        if (($validated['recurrence_type'] ?? null) === 'one_time') {
            $validated['recurrence_ends_at'] = null;
        }

        unset($validated['category_public_id']);

        return $validated;
    }

    protected function resolvedCategory(): ?TaskCategory
    {
        if (! $this->filled('category_public_id')) {
            return null;
        }

        return $this->user()
            ?->categories()
            ->where('public_id', $this->string('category_public_id')->toString())
            ->first();
    }

    protected function validateCategoryOwnership(Validator $validator): void
    {
        if (! $this->filled('category_public_id')) {
            return;
        }

        if (! $this->resolvedCategory()) {
            $validator->errors()->add('category_public_id', __('The selected category is invalid.'));
        }
    }

    protected function validateRecurrenceFields(Validator $validator): void
    {
        $recurrenceType = $this->string('recurrence_type')->toString();

        if ($recurrenceType === 'specific_days' && count($this->array('recurrence_days')) === 0) {
            $validator->errors()->add('recurrence_days', __('Select at least one recurrence day.'));
        }

        if ($recurrenceType === 'one_time' && ! $this->filled('recurrence_starts_at')) {
            $validator->errors()->add('recurrence_starts_at', __('The recurrence start date is required for one-time tasks.'));
        }

        if ($recurrenceType === 'x_days' && ! $this->filled('recurrence_ends_at')) {
            $validator->errors()->add('recurrence_ends_at', __('The recurrence end date is required for x-days tasks.'));
        }

        if ($recurrenceType === 'date_range') {
            if (! $this->filled('recurrence_starts_at')) {
                $validator->errors()->add('recurrence_starts_at', __('The recurrence start date is required for date range tasks.'));
            }

            if (! $this->filled('recurrence_ends_at')) {
                $validator->errors()->add('recurrence_ends_at', __('The recurrence end date is required for date range tasks.'));
            }
        }
    }
}
