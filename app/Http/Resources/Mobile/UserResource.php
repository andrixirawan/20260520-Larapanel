<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'has_custom_avatar' => $this->has_custom_avatar,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_email_verified' => $this->hasVerifiedEmail(),
            'two_factor_enabled' => method_exists($this->resource, 'hasEnabledTwoFactorAuthentication')
                ? $this->hasEnabledTwoFactorAuthentication()
                : false,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
