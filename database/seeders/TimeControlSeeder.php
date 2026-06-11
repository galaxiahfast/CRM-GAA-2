<?php

namespace Database\Seeders;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class TimeControlSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Auxiliar de Auditoría', 'Auxiliar de Nómina', 'Contador Senior'] as $name) {
            JobPosition::firstOrCreate(['name' => $name]);
        }

        foreach (['Informática', 'Contabilidad', 'Dirección'] as $name) {
            PhysicalArea::firstOrCreate(['name' => $name]);
        }

        // Usuario operativo de prueba (rol Auxiliar) con perfil activo.
        $aux = User::firstOrCreate(
            ['email' => 'auxiliar@datamid.com.mx'],
            [
                'name' => 'Auxiliar',
                'last_name' => 'Demo',
                'password' => Hash::make('Datamid2025.'),
                'role_id' => 4,
            ]
        );

        if (! $aux->activeOrganizationalProfile()->exists()) {
            UserOrganizationalProfile::create([
                'user_id' => $aux->id,
                'job_position_id' => JobPosition::where('name', 'Auxiliar de Nómina')->value('id'),
                'physical_area_id' => PhysicalArea::where('name', 'Contabilidad')->value('id'),
                'valid_from' => Carbon::now()->toDateString(),
                'valid_to' => null,
                'is_active' => true,
            ]);
        }
    }
}
