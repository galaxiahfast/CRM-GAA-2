<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_delivery_reports', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('movement_type')
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('delivered_by', 120)
                ->default('Julián Emiliano Ortiz Rivero')
                ->after('customer_contact');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_delivery_reports', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'delivered_by']);
        });
    }
};
