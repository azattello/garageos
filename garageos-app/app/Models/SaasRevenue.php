<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasRevenue extends Model
{
    protected $fillable = [
        'organization_id', 'payment_id', 'category', 'amount', 'currency', 'received_at', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'received_at' => 'date'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function payment(): BelongsTo { return $this->belongsTo(SaasPayment::class, 'payment_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}