<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('status_id')->constrained('order_statuses')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('planned_completion_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid_total')->default(0);
            $table->unsignedBigInteger('balance')->default(0);
            $table->text('internal_comment')->nullable();
            $table->text('client_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status_id']);
            $table->index(['organization_id', 'client_id']);
            $table->index(['organization_id', 'car_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};