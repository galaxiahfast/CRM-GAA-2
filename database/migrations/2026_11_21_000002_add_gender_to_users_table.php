<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Género (opcional, como el año de nacimiento): 'hombre' o 'mujer'. Se usa para elegir la ilustración que se muestra
 * cuando la persona no tiene foto; si no lo captura, se deduce de su nombre.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'gender')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'gender')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
