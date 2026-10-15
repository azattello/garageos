<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('reserved_stock', 10, 2)->default(0)->after('stock');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('reserved_delta', 10, 2)->default(0)->after('quantity');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('inventory_status')->default('none')->after('total');
        });

        DB::table('order_items')
            ->where('type', 'part')
            ->whereNotNull('part_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('inventory_transactions')
                    ->whereColumn('inventory_transactions.organization_id', 'order_items.organization_id')
                    ->whereColumn('inventory_transactions.part_id', 'order_items.part_id')
                    ->whereColumn('inventory_transactions.reference_id', 'order_items.order_id')
                    ->where('inventory_transactions.reference_type', 'order')
                    ->where('inventory_transactions.type', 'issue');
            })
            ->update(['inventory_status' => 'issued']);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('inventory_status');
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('reserved_stock');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('reserved_delta');
        });
    }
};