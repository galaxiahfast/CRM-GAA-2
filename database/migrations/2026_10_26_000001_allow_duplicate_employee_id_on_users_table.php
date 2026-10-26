<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El ID del reloj checador puede repetirse entre personas: se cambia el índice único por uno normal.
     */
    public function up(): void
    {
        if (Schema::hasIndex('users', 'users_employee_id_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_employee_id_unique');
            });
        }

        if (! Schema::hasIndex('users', 'users_employee_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('employee_id');
            });
        }
    }

    public function down(): void
    {
        $hasDuplicateIds = DB::table('users')
            ->whereNotNull('employee_id')
            ->where('employee_id', '<>', '')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateIds) {
            throw new RuntimeException('No se puede restaurar el índice único de users.employee_id mientras existan IDs de checador repetidos.');
        }

        if (Schema::hasIndex('users', 'users_employee_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_employee_id_index');
            });
        }

        // Solo se puede volver a único si no hay IDs repetidos.
        if (! Schema::hasIndex('users', 'users_employee_id_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('employee_id');
            });
        }
    }
};
