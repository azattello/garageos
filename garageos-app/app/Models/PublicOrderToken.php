<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicOrderToken extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'order_id', 'token_hash', 'expires_at', 'revoked_at'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}