<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateExpense
{
    public function handle(User $user, Expense $expense, array $data): Expense
    {
        abort_unless($user->organization_id === $expense->organization_id && in_array($user->role, ['owner', 'manager'], true), 404);

        return DB::transaction(function () use ($user, $expense, $data): Expense {
            $lockedExpense = Expense::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->findOrFail($expense->id);
            $category = ExpenseCategory::where('organization_id', $user->organization_id)
                ->where('active', true)
                ->lockForUpdate()
                ->find($data['category_id']);
            if (! $category) {
                throw ValidationException::withMessages(['category_id' => 'Категория не найдена в вашей организации.']);
            }

            $before = ['amount' => (int) $lockedExpense->amount, 'category_id' => $lockedExpense->category_id, 'expense_date' => $lockedExpense->expense_date->toDateString()];
            $lockedExpense->update([
                'category_id' => $category->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'expense_date' => $data['expense_date'],
            ]);
            Activity::record('finance.expense_updated', $lockedExpense, $user, [
                'before' => $before,
                'after' => ['amount' => (int) $lockedExpense->amount, 'category_id' => $category->id, 'expense_date' => $lockedExpense->expense_date->toDateString()],
            ]);

            return $lockedExpense->refresh();
        });
    }
}
