<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Un colaborador de ManagerControl puede asignarse a varias cuentas del CRM (cada cuenta sigue teniendo un solo colaborador). */
    public function up(): void
    {
        Schema::table('managercontrol_employee_links', function (Blueprint $table): void {
            $table->dropUnique(['legacy_employee_id']);
            $table->index('legacy_employee_id');
        });
    }

    public function down(): void
    {
        $repeated = DB::table('managercontrol_employee_links')->select('legacy_employee_id')->groupBy('legacy_employee_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($repeated) {
            throw new RuntimeException('Hay colaboradores asignados a varias cuentas: quita los repetidos antes de revertir esta migración.');
        }
        Schema::table('managercontrol_employee_links', function (Blueprint $table): void {
            $table->dropIndex(['legacy_employee_id']);
            $table->unique('legacy_employee_id');
        });
    }
};
