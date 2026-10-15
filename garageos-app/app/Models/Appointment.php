<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'client_id', 'car_id', 'mechanic_id', 'service_id', 'status_id', 'order_id', 'start_at', 'end_at', 'status', 'comment', 'created_by'];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function car(): BelongsTo { return $this->belongsTo(Car::class); }
    public function mechanic(): BelongsTo { return $this->belongsTo(User::class, 'mechanic_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function statusDefinition(): BelongsTo { return $this->belongsTo(AppointmentStatus::class, 'status_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}