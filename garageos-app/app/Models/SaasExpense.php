<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasExpense extends Model
{
    protected $fillable = ['category', 'vendor', 'description', 'amount', 'currency', 'expense_date', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'expense_date' => 'date'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}