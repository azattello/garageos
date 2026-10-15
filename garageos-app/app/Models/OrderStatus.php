<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'slug', 'sort_order', 'is_terminal'];
    protected function casts(): array { return ['is_terminal' => 'boolean']; }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class, 'status_id'); }
}