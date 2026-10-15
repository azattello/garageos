<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaasPayment extends Model
{
    protected $fillable = [
        'organization_id', 'subscription_id', 'subscription_change_request_id', 'plan', 'amount',
        'currency', 'method', 'status', 'reference', 'description', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function changeRequest(): BelongsTo { return $this->belongsTo(SubscriptionChangeRequest::class, 'subscription_change_request_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function revenue(): HasOne { return $this->hasOne(SaasRevenue::class, 'payment_id'); }
}