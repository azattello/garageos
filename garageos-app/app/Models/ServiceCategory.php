<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function services(): HasMany { return $this->hasMany(Service::class, 'category_id'); }
}
