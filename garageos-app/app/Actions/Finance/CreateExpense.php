<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateExpense
{
    public function handle(User $user, array $data): Expense
    {
        abort_unless($user->organization_id && in_array($user->role, ['owner', 'manager'], true), 403);

        return DB::transaction(function () use ($user, $data): Expense {
            $category = ExpenseCategory::where('organization_id', $user->organization_id)
                ->where('active', true)
                ->lockForUpdate()
                ->find($data['category_id']);
            if (! $category) {
                throw ValidationException::withMessages(['category_id' => 'Категория не найдена в вашей организации.']);
            }

            $expense = Expense::create([
                'organization_id' => $user->organization_id,
                'category_id' => $category->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'expense_date' => $data['expense_date'],
                'created_by' => $user->id,
            ]);
            Activity::record('finance.expense_created', $expense, $user, ['amount' => (int) $expense->amount, 'category_id' => $category->id]);

            return $expense;
        });
    }
}
