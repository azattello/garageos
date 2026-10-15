<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;

class ArchiveExpense
{
    public function handle(User $user, Expense $expense): void
    {
        abort_unless($user->organization_id === $expense->organization_id && in_array($user->role, ['owner', 'manager'], true), 404);

        DB::transaction(function () use ($user, $expense): void {
            $lockedExpense = Expense::where('organization_id', $user->organization_id)
                ->lockForUpdate()
                ->findOrFail($expense->id);
            $lockedExpense->delete();
            Activity::record('finance.expense_archived', $lockedExpense, $user, [
                'amount' => (int) $lockedExpense->amount,
                'category_id' => $lockedExpense->category_id,
            ]);
        });
    }
}
