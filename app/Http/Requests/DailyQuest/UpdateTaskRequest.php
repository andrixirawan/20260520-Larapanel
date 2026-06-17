<?php

namespace App\Http\Requests\DailyQuest;

class UpdateTaskRequest extends StoreTaskRequest
{
    /**
     * @return array<string, mixed>
     */
    public function taskAttributes(): array
    {
        $attributes = parent::taskAttributes();
        $attributes['is_active'] = $attributes['is_active'] ?? $this->route('task')?->is_active ?? true;

        return $attributes;
    }
}
