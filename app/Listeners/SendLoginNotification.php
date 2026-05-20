<?php

namespace App\Listeners;

use App\Mail\AuthActivityNotification;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Mail;

class SendLoginNotification
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        Mail::to($event->user->email)->send(
            new AuthActivityNotification(
                activity: 'Login',
                userName: $event->user->name,
                ipAddress: request()->ip() ?? 'Unknown',
                userAgent: request()->userAgent(),
                occurredAt: now()->toImmutable(),
            ),
        );
    }
}
