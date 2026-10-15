<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('plate_number', 24)->nullable();
            $table->string('vin', 32)->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('fuel_type')->nullable();
            $table->text('comment')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'plate_number']);
            $table->index(['organization_id', 'vin']);
            $table->index(['organization_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};