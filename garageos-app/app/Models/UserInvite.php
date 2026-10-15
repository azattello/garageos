<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvite extends Model
{
    protected $fillable = ['organization_id', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at', 'invited_by'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'accepted_at' => 'datetime']; }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}