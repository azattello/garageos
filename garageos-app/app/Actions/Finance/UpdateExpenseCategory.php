<?php

namespace App\Actions\Finance;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateExpenseCategory
{
    public function handle(User $user, ExpenseCategory $category, string $name): ExpenseCategory
    {
        abort_unless($user->organization_id === $category->organization_id && in_array($user->role, ['owner', 'manager'], true), 404);
        $validated = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:120', Rule::unique('expense_categories', 'name')->where(fn ($query) => $query->where('organization_id', $user->organization_id)->where('id', '!=', $category->id))],
        ])->validate();

        return DB::transaction(function () use ($user, $category, $validated): ExpenseCategory {
            $lockedCategory = ExpenseCategory::where('organization_id', $user->organization_id)->lockForUpdate()->findOrFail($category->id);
            $before = $lockedCategory->name;
            $lockedCategory->update(['name' => $validated['name']]);
            Activity::record('finance.expense_category_updated', $lockedCategory, $user, ['before' => $before, 'after' => $lockedCategory->name]);

            return $lockedCategory->refresh();
        });
    }
}
