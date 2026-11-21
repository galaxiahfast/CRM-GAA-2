<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de cada persona dentro del equipo de su jefe (de izquierda a derecha en el organigrama). Al arrastrar a alguien
 * junto a otra persona queda exactamente en ese lugar. Si no tiene orden se dibuja al final, en el orden en que se asignó.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_hierarchy_relations', 'position')) {
            return;
        }

        Schema::table('user_hierarchy_relations', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('superior_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('user_hierarchy_relations', 'position')) {
            return;
        }

        Schema::table('user_hierarchy_relations', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
