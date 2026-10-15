<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'sku', 'supplier', 'cost_price', 'sale_price', 'stock', 'reserved_stock', 'min_stock', 'unit', 'active'];

    protected function casts(): array
    {
        return ['cost_price' => 'integer', 'sale_price' => 'integer', 'stock' => 'decimal:2', 'reserved_stock' => 'decimal:2', 'min_stock' => 'decimal:2', 'active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function transactions(): HasMany { return $this->hasMany(InventoryTransaction::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
