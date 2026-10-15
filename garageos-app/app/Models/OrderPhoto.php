<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'order_id', 'car_id', 'path', 'storage_disk', 'category', 'caption', 'visible_to_client', 'uploaded_by'];
    protected function casts(): array { return ['visible_to_client' => 'boolean']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}