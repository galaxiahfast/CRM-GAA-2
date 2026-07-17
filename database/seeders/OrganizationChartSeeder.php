<?php

namespace Database\Seeders;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class OrganizationChartSeeder extends Seeder
{
    /**
     * Datos piloto aditivos para Contaduría.
     * No modifica usuarios ni relaciones existentes.
     */
    public function run(): void
    {
        if (UserHierarchyRelation::query()->exists()) {
            return;
        }

        $positions = [];
        foreach (['Contador', 'Auxiliar Contable', 'Becario Senior', 'Becario'] as $name) {
            $positions[$name] = JobPosition::firstOrCreate(['name' => $name]);
        }

        $contabilidadArea = PhysicalArea::firstOrCreate(['name' => 'Contabilidad']);

        $contadorRole = Role::where('role', 'Contador')->first();
        $auxiliarRole = Role::where('role', 'Auxiliar')->first();

        if (! $contadorRole || ! $auxiliarRole) {
            return;
        }

        $contador = User::firstOrCreate(
            ['email' => 'contador.piloto@datamid.com.mx'],
            [
                'name' => 'Contador',
                'last_name' => 'Piloto',
                'password' => Hash::make('Datamid2025.'),
                'role_id' => $contadorRole->id,
            ]
        );

        $auxiliarContable = User::firstOrCreate(
            ['email' => 'aux.contable.piloto@datamid.com.mx'],
            [
                'name' => 'Auxiliar',
                'last_name' => 'Contable',
                'password' => Hash::make('Datamid2025.'),
                'role_id' => $auxiliarRole->id,
            ]
        );

        $becarioSenior = User::firstOrCreate(
            ['email' => 'becario.senior.piloto@datamid.com.mx'],
            [
                'name' => 'Becario',
                'last_name' => 'Senior',
                'password' => Hash::make('Datamid2025.'),
                'role_id' => $auxiliarRole->id,
            ]
        );

        $becario = User::firstOrCreate(
            ['email' => 'becario.piloto@datamid.com.mx'],
            [
                'name' => 'Becario',
                'last_name' => 'Piloto',
                'password' => Hash::make('Datamid2025.'),
                'role_id' => $auxiliarRole->id,
            ]
        );

        $pilotUsers = [
            [$contador, $positions['Contador']],
            [$auxiliarContable, $positions['Auxiliar Contable']],
            [$becarioSenior, $positions['Becario Senior']],
            [$becario, $positions['Becario']],
        ];

        foreach ($pilotUsers as [$user, $position]) {
            if (! $user->activeOrganizationalProfile()->exists()) {
                UserOrganizationalProfile::create([
                    'user_id' => $user->id,
                    'job_position_id' => $position->id,
                    'physical_area_id' => $contabilidadArea->id,
                    'valid_from' => Carbon::now()->toDateString(),
                    'valid_to' => null,
                    'is_active' => true,
                ]);
            }
        }

        $chartService = app(OrganizationChartService::class);

        $relations = [
            [$auxiliarContable->id, $contador->id, $positions['Auxiliar Contable']->id],
            [$becarioSenior->id, $auxiliarContable->id, $positions['Becario Senior']->id],
            [$becario->id, $becarioSenior->id, $positions['Becario']->id],
        ];

        foreach ($relations as [$subordinateId, $superiorId, $jobPositionId]) {
            $chartService->createRelation([
                'subordinate_id' => $subordinateId,
                'superior_id' => $superiorId,
                'job_position_id' => $jobPositionId,
                'physical_area_id' => $contabilidadArea->id,
            ]);
        }
    }
}
