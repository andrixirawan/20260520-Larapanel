<?php

namespace App\Listeners;

use App\Mail\AuthActivityNotification;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Mail;

class SendLogoutNotification
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        Mail::to($event->user->email)->send(
            new AuthActivityNotification(
                activity: 'Logout',
                userName: $event->user->name,
                ipAddress: request()->ip() ?? 'Unknown',
                userAgent: request()->userAgent(),
                occurredAt: now()->toImmutable(),
            ),
        );
    }
}
