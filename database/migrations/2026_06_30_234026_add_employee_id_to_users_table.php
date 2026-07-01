<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Creamos la columna como string, permitiendo que sea NULL (por si un admin no usa checador)
            // La colocamos justo después de la columna 'last_name' usando after()
            $table->string('employee_id')->nullable()->after('last_name')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Regla de reversión obligatoria por si necesitas hacer un rollback
            $table->dropColumn('employee_id');
        });
    }
};