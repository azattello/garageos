<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_change_request_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('plan')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('KZT');
            $table->string('method')->default('bank_transfer');
            $table->string('status')->default('paid')->index();
            $table->string('reference')->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'paid_at']);
        });

        Schema::create('saas_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained('saas_payments')->nullOnDelete();
            $table->string('category')->default('other')->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('KZT');
            $table->date('received_at')->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'received_at']);
        });

        Schema::create('saas_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('KZT');
            $table->date('expense_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_expenses');
        Schema::dropIfExists('saas_revenues');
        Schema::dropIfExists('saas_payments');
    }
};