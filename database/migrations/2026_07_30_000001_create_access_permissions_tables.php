<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('module')->nullable()->index();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('role_access_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('access_permission_id')->constrained('access_permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'access_permission_id'], 'role_access_permission_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_access_permission');
        Schema::dropIfExists('access_permissions');
    }
};
