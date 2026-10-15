<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'category_id', 'amount', 'description', 'expense_date', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'expense_date' => 'date'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'category_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
