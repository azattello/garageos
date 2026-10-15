<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('supplier')->nullable();
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('sale_price')->default(0);
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->string('unit')->default('шт');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'active']);
            $table->index(['organization_id', 'sku']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('quantity', 10, 2);
            $table->unsignedBigInteger('unit_cost')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'part_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('parts');
    }
};
