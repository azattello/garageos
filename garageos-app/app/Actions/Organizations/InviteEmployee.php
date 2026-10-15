<?php

namespace App\Actions\Organizations;

use App\Models\Notification;
use App\Models\UserInvite;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InviteEmployee
{
    /** @return array{invite: UserInvite, token: string} */
    public function handle(User $user, array $data): array
    {
        Gate::forUser($user)->authorize('create', UserInvite::class);

        $subscription = $user->organization?->subscription()->first();
        if ($subscription && ! $subscription->hasAvailableSeats(1)) {
            abort(403, 'Лимит пользователей по тарифу достигнут.');
        }

        $token = Str::random(64);
        $invite = UserInvite::create(['organization_id' => $user->organization_id, 'email' => $data['email'], 'role' => $data['role'], 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7), 'invited_by' => $user->id]);
        Notification::create(['organization_id' => $user->organization_id, 'user_id' => $user->id, 'type' => 'employee_invited', 'title' => 'Приглашение создано', 'body' => "Приглашение отправлено на {$data['email']}.", 'data' => ['invite_id' => $invite->id]]);
        Activity::record('employee.invited', $invite, $user, ['email' => $invite->email, 'role' => $invite->role]);
        return ['invite' => $invite, 'token' => $token];
    }
}
