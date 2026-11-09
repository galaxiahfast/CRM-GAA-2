<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Models\Role;
use App\Models\User;
use App\Services\ReferenceDataCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El directorio de Administración pesa ~180 KB: viaja al abrir la página y solo se vuelve a enviar si cambia.
 */
class AdministrationPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_directory_is_sent_on_load_skipped_on_updates_and_sent_again_when_it_changes(): void
    {
        $role = Role::create(['role' => 'Administrador']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-payload@test.mx', 'password' => Hash::make('secret'), 'role_id' => $role->id]);

        $component = Livewire::actingAs($admin)->test(PanelAdministracion::class);
        $component->assertSeeHtml('catalogs: JSON.parse');

        // Una actualización cualquiera (como las de Livewire en el navegador) ya no lleva el directorio.
        $component->call('setActiveTab', 'datos')
            ->assertDontSeeHtml('catalogs: JSON.parse');

        // Al invalidarse el directorio (por ejemplo, tras editar un rol) cambia su sello y vuelve a enviarse una vez.
        app(ReferenceDataCache::class)->forgetAdministration();
        $component->call('refreshOrganizationDirectory')
            ->assertSeeHtml('catalogs: JSON.parse')
            ->call('setActiveTab', 'datos')
            ->assertDontSeeHtml('catalogs: JSON.parse');
    }
}
