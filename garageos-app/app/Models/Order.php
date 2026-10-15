<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'number', 'client_id', 'car_id', 'mechanic_id', 'status_id', 'opened_at', 'planned_completion_at', 'completed_at', 'subtotal', 'discount', 'total', 'paid_total', 'balance', 'internal_comment', 'client_comment', 'created_by'];
    protected function casts(): array { return ['opened_at' => 'datetime', 'planned_completion_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function car(): BelongsTo { return $this->belongsTo(Car::class); }
    public function mechanic(): BelongsTo { return $this->belongsTo(User::class, 'mechanic_id'); }
    public function status(): BelongsTo { return $this->belongsTo(OrderStatus::class, 'status_id'); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function photos(): HasMany { return $this->hasMany(OrderPhoto::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}