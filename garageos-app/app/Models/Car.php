<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'client_id', 'make', 'model', 'year', 'plate_number', 'vin', 'color', 'mileage', 'fuel_type', 'comment', 'photo_path', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
}