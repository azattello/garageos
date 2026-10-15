<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('active')->index();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};