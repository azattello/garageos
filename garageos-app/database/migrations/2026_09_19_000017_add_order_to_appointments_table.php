<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('service_id')->constrained('orders')->nullOnDelete();
            $table->index(['organization_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['organization_id', 'order_id']);
            $table->dropColumn('order_id');
        });
    }
};