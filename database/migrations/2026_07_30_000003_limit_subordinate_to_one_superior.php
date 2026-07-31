<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si hay datos históricos con varios jefes, conserva la relación más
        // antigua antes de aplicar la regla actual de un jefe por subordinado.
        DB::table('user_hierarchy_relations')
            ->select('subordinate_id')
            ->groupBy('subordinate_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('subordinate_id')
            ->each(function ($subordinateId): void {
                $relationIds = DB::table('user_hierarchy_relations')
                    ->where('subordinate_id', $subordinateId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id');

                DB::table('user_hierarchy_relations')
                    ->whereIn('id', $relationIds->slice(1)->all())
                    ->delete();
            });

        Schema::table('user_hierarchy_relations', function (Blueprint $table): void {
            $table->unique('subordinate_id', 'user_hierarchy_one_superior_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_hierarchy_relations', function (Blueprint $table): void {
            $table->dropUnique('user_hierarchy_one_superior_unique');
        });
    }
};
