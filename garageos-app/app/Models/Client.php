<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'phone', 'email', 'comment', 'status'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function cars(): HasMany { return $this->hasMany(Car::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function portalUsers(): BelongsToMany { return $this->belongsToMany(User::class, 'client_users')->withPivot('organization_id')->withTimestamps(); }
}