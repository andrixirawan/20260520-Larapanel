<?php

namespace App\Http\Resources\DailyQuest;

use App\Models\DailyQuest\TaskInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskInstance
 */
class TaskInstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'points_awarded' => $this->points_awarded,
            'notes' => $this->notes,
            'task' => $this->whenLoaded('task', fn (): array => TaskResource::make($this->task)->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
