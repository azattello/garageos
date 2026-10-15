<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'sort_order']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('organization_id')->constrained('service_categories')->nullOnDelete();
            $table->index(['organization_id', 'category_id']);
        });

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach (['ТО', 'Диагностика', 'Двигатель', 'Ходовая', 'Тормоза', 'Электрика', 'Детейлинг', 'Другое'] as $index => $name) {
                DB::table('service_categories')->insert(['organization_id' => $organizationId, 'name' => $name, 'sort_order' => $index + 1, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['organization_id', 'category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('service_categories');
    }
};
