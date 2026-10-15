<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'sort_order']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('service_id')->constrained('appointment_statuses')->nullOnDelete();
            $table->index(['organization_id', 'status_id']);
        });

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach ([['pending', 'Ожидает'], ['confirmed', 'Подтверждена'], ['arrived', 'Клиент приехал'], ['completed', 'Завершена'], ['cancelled', 'Отменена']] as $index => [$slug, $name]) {
                DB::table('appointment_statuses')->insert(['organization_id' => $organizationId, 'name' => $name, 'slug' => $slug, 'sort_order' => $index + 1, 'is_terminal' => in_array($slug, ['completed', 'cancelled'], true), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropIndex(['organization_id', 'status_id']);
            $table->dropColumn('status_id');
        });
        Schema::dropIfExists('appointment_statuses');
    }
};
