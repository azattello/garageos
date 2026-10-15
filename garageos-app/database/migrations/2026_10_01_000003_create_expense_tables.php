<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_CATEGORIES = ['Аренда', 'Зарплата', 'Запчасти', 'Расходники', 'Реклама', 'Коммунальные услуги', 'Оборудование', 'Транспорт', 'Другое'];

    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'active', 'sort_order']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('description')->nullable();
            $table->date('expense_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'expense_date']);
            $table->index(['organization_id', 'category_id', 'expense_date']);
        });

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach (self::DEFAULT_CATEGORIES as $index => $name) {
                DB::table('expense_categories')->insert([
                    'organization_id' => $organizationId,
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
