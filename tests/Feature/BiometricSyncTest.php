<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BiometricSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_adds_missing_marks_without_changing_existing_local_edits(): void
    {
        config()->set('services.biometric.password', 'test-only-password');
        $existingTime = now()->subDay()->startOfMinute()->format('Y-m-d H:i:s');
        $newTime = now()->subHours(2)->startOfMinute()->format('Y-m-d H:i:s');

        DB::table('control_de_horas')->insert([
            'employeeID' => '101',
            'personName' => 'Nombre corregido localmente',
            'authDateTime' => $existingTime,
            'authDate' => substr($existingTime, 0, 10),
            'authTime' => substr($existingTime, 11),
            'direction' => 'OUT',
            'deviceName' => 'Corrección local',
        ]);

        Http::fake(['*/ISAPI/AccessControl/AcsEvent?format=json' => Http::response([
            'AcsEvent' => [
                'totalMatches' => 2,
                'InfoList' => [
                    ['employeeNo' => '101', 'name' => 'Nombre del dispositivo', 'time' => str_replace(' ', 'T', $existingTime), 'attendanceStatus' => 'checkIn'],
                    ['employeeNo' => '102', 'name' => 'Persona nueva', 'time' => str_replace(' ', 'T', $newTime), 'attendanceStatus' => 'checkIn'],
                ],
            ],
        ])]);

        $this->assertSame(0, Artisan::call('biometric:sync', ['--maintenance' => true]));
        $this->assertDatabaseHas('control_de_horas', [
            'employeeID' => '101', 'authDateTime' => $existingTime,
            'personName' => 'Nombre corregido localmente', 'direction' => 'OUT',
        ]);
        $this->assertDatabaseHas('control_de_horas', ['employeeID' => '102', 'authDateTime' => $newTime]);
        $this->assertSame(2, DB::table('control_de_horas')->count());
    }

    public function test_sync_stops_before_contacting_device_when_password_is_missing(): void
    {
        config()->set('services.biometric.password', null);
        Http::fake();

        $this->assertSame(1, Artisan::call('biometric:sync'));
        Http::assertNothingSent();
    }
}
