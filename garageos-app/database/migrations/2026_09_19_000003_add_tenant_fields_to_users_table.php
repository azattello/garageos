<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('owner')->after('phone')->index();
            $table->string('status')->default('active')->after('role')->index();
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'role']);
            $table->dropColumn(['organization_id', 'phone', 'role', 'status', 'last_login_at']);
        });
    }
};