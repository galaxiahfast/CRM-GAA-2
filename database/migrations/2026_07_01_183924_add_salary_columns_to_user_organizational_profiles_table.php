<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_organizational_profiles', function (Blueprint $table) {
            // Precio por hora (Por defecto 25.00)
            $table->decimal('hourly_rate', 8, 2)->default(25.00);
            
            // Apoyo de comida por día trabajado (Por defecto 50.00)
            $table->decimal('food_allowance', 8, 2)->default(50.00);
        });

        Schema::table('time_entries', function (Blueprint $table) {
            // Para poder agregar un bono extra directamente a un día o registro específico
            $table->decimal('bonus', 8, 2)->default(0.00);
            $table->string('bonus_reason', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_organizational_profiles', function (Blueprint $table) {
            $table->dropColumn(['hourly_rate', 'food_allowance']);
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['bonus', 'bonus_reason']);
        });
    }
};