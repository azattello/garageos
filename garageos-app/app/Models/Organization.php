<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'phone',
        'email',
        'timezone',
        'currency',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function subscriptionChangeRequests(): HasMany
    {
        return $this->hasMany(SubscriptionChangeRequest::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function appointmentStatuses(): HasMany
    {
        return $this->hasMany(AppointmentStatus::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}