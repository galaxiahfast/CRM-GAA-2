<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->select(['COLUMN_NAME', 'CONSTRAINT_NAME'])
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'user_organizational_profiles')
                ->whereIn('COLUMN_NAME', ['job_position_id', 'physical_area_id'])
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->get();

            foreach ($foreignKeys as $foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `user_organizational_profiles` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey->CONSTRAINT_NAME)
                ));
            }
        } else {
            Schema::table('user_organizational_profiles', function (Blueprint $table): void {
                $table->dropForeign(['job_position_id']);
                $table->dropForeign(['physical_area_id']);
            });
        }

        Schema::table('user_organizational_profiles', function (Blueprint $table): void {
            $table->unsignedBigInteger('job_position_id')->nullable()->change();
            $table->unsignedBigInteger('physical_area_id')->nullable()->change();

            $table->foreign('job_position_id', 'uop_job_position_nullable_foreign')
                ->references('id')
                ->on('job_positions')
                ->nullOnDelete();
            $table->foreign('physical_area_id', 'uop_physical_area_nullable_foreign')
                ->references('id')
                ->on('physical_areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Se conserva como no reversible: restaurar NOT NULL requeriría inventar
        // datos para los perfiles que quedaron correctamente sin puesto o área.
    }
};
