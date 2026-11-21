<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de nacimiento completa (opcional). Antes solo se guardaba el año (birth_year); el año se sigue llenando a partir
 * de la fecha para lo que ya lo usa (edad aproximada).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'birth_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'birth_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
