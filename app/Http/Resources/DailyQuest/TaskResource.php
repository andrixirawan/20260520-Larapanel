<?php

namespace App\Http\Resources\DailyQuest;

use App\Models\DailyQuest\Task;
use App\Support\DailyQuestPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'points' => $this->points,
            'recurrence_type' => $this->recurrence_type,
            'recurrence_days' => $this->recurrence_days,
            'recurrence_starts_at' => $this->recurrence_starts_at?->toDateString(),
            'recurrence_ends_at' => $this->recurrence_ends_at?->toDateString(),
            'recurrence_summary' => DailyQuestPayload::recurrenceSummary($this->resource),
            'is_active' => $this->is_active,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'category' => $this->whenLoaded('category', fn (): array => TaskCategoryResource::make($this->category)->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
