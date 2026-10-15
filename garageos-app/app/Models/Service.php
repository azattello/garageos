<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'category_id', 'name', 'description', 'price', 'duration_minutes', 'active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'duration_minutes' => 'integer', 'active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class, 'category_id'); }
}