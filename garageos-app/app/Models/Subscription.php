<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Billing\PlanCatalog;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'plan',
        'status',
        'max_users',
        'trial_ends_at',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(SubscriptionChangeRequest::class);
    }

    public function saasPayments(): HasMany
    {
        return $this->hasMany(SaasPayment::class);
    }

    public function hasAvailableSeats(int $additionalUsers = 1): bool
    {
        $maxUsers = app(PlanCatalog::class)->get($this->plan)['max_users'] ?? (int) ($this->max_users ?? 0);

        if ($maxUsers <= 0) {
            return false;
        }

        $usedUsers = (int) $this->organization()
            ->withCount(['users' => fn ($query) => $query->whereNotIn('role', ['client', 'platform_admin'])])
            ->first()?->users_count ?? 0;

        return ($usedUsers + $additionalUsers) <= $maxUsers;
    }

    public function hasFeature(string $feature): bool
    {
        return app(PlanCatalog::class)->includes($this->plan, $feature);
    }
}