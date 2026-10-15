<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'order_id', 'type', 'service_id', 'part_id', 'name_snapshot', 'quantity', 'unit_price', 'unit_cost', 'discount', 'total', 'inventory_status'];
    protected function casts(): array { return ['quantity' => 'decimal:2']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function part(): BelongsTo { return $this->belongsTo(Part::class); }
}