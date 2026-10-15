<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = ['organization_id', 'part_id', 'type', 'quantity', 'reserved_delta', 'unit_cost', 'user_id', 'reference_type', 'reference_id', 'note'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'reserved_delta' => 'decimal:2', 'unit_cost' => 'integer'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function part(): BelongsTo { return $this->belongsTo(Part::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
