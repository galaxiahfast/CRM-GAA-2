<?php

use App\Models\JobPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table): void {
            $table->string('payment_type', 20)
                ->default(JobPosition::PAYMENT_FULL_TIME)
                ->after('name')
                ->index();
        });

        // Conserva el comportamiento previo: los puestos utilizados por un
        // Auxiliar ya tenían tarifa por hora y apoyo económico configurables.
        $hourlyPositionIds = DB::table('user_organizational_profiles as profile')
            ->join('users as user', 'user.id', '=', 'profile.user_id')
            ->join('roles as role', 'role.id', '=', 'user.role_id')
            ->where('role.role', 'Auxiliar')
            ->whereNotNull('profile.job_position_id')
            ->distinct()
            ->pluck('profile.job_position_id');

        DB::table('job_positions')
            ->whereIn('id', $hourlyPositionIds)
            ->update(['payment_type' => JobPosition::PAYMENT_HOURLY]);
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table): void {
            $table->dropIndex(['payment_type']);
            $table->dropColumn('payment_type');
        });
    }
};
