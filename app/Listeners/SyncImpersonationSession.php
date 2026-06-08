<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Auth;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class SyncImpersonationSession
{
    public function handleTake(TakeImpersonation $event): void
    {
        $this->syncPasswordHash($event->impersonated);
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        $this->syncPasswordHash($event->impersonator);
    }

    private function syncPasswordHash($user): void
    {
        if (! $user) {
            return;
        }

        $guard = Auth::getDefaultDriver();

        session()->put([
            'password_hash_'.$guard => $user->getAuthPassword(),
        ]);
    }
}
