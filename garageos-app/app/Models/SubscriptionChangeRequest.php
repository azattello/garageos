<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionChangeRequest extends Model
{
    protected $fillable = [
        'organization_id', 'subscription_id', 'requested_by', 'current_plan', 'requested_plan',
        'quoted_amount', 'currency', 'status', 'payment_reference', 'reviewed_by', 'review_note', 'requested_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
