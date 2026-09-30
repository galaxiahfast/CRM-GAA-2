<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_delivery_folio_counters', function (Blueprint $table): void {
            $table->date('date')->primary();
            $table->unsignedSmallInteger('last_number');
            $table->timestamps();
        });

        Schema::create('equipment_delivery_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 32)->unique();
            $table->uuid('request_token')->unique();
            $table->string('movement_type', 30)->index();
            $table->string('customer_name', 120);
            $table->string('customer_contact', 120)->default('');
            $table->string('equipment_type', 80);
            $table->string('brand', 100)->default('');
            $table->string('model', 100);
            $table->string('serial_number', 100)->default('');
            $table->string('accessories', 300)->default('');
            $table->string('physical_condition', 30);
            $table->string('observations', 600)->default('');
            $table->string('photo_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at', 'id'], 'equipment_delivery_created_index');
            $table->index('serial_number', 'equipment_delivery_serial_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_delivery_reports');
        Schema::dropIfExists('equipment_delivery_folio_counters');
    }
};
