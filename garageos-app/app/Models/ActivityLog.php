<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['organization_id', 'user_id', 'action', 'subject_type', 'subject_id', 'metadata', 'ip_address', 'user_agent', 'created_at'];
    protected function casts(): array { return ['metadata' => 'array', 'created_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}