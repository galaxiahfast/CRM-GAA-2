<?php

namespace Tests\Feature;

use App\Livewire\Profile\UpdateProfileInformationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->last_name, $component->state['last_name']);
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('startEditing')
            ->assertSet('editing', true)
            ->set('state', [
                'name' => 'Test Name',
                'last_name' => 'Updated Last Name',
                'email' => 'test@example.com',
                'profile_description' => 'Descripción visible del perfil.',
                'instagram_url' => 'https://instagram.com/test',
                'facebook_url' => 'https://facebook.com/test',
            ])
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Editar perfil')
            ->assertDontSee('Guardar cambios')
            ->assertSet('state.name', 'TEST NAME')
            ->assertSet('state.profile_description', 'Descripción visible del perfil.');

        $this->assertEquals('TEST NAME', $user->fresh()->name);
        $this->assertEquals('UPDATED LAST NAME', $user->fresh()->last_name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
        $this->assertEquals('Descripción visible del perfil.', $user->fresh()->profile_description);
        $this->assertEquals('https://instagram.com/test', $user->fresh()->instagram_url);
        $this->assertEquals('https://facebook.com/test', $user->fresh()->facebook_url);
    }

    public function test_profile_starts_in_read_only_mode_and_can_enter_edit_mode(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->assertSet('editing', false)
            ->call('startEditing')
            ->assertSet('editing', true)
            ->call('cancelEditing')
            ->assertSet('editing', false);
    }

    public function test_social_links_must_be_valid_urls(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state.instagram_url', 'instagram sin url')
            ->call('updateProfileInformation')
            ->assertHasErrors(['instagram_url']);
    }

    public function test_a_failed_save_stays_in_edit_mode_and_explains_why(): void
    {
        $this->actingAs(User::factory()->create(['instagram_url' => 'instagram.com/sin-esquema']));

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('startEditing')
            ->call('updateProfileInformation')
            ->assertHasErrors(['instagram_url'])
            ->assertSet('editing', true)
            ->assertSee('No se guardaron los cambios');
    }

    public function test_an_autofilled_current_password_does_not_require_a_new_one(): void
    {
        $this->actingAs($user = User::factory()->create(['password' => Hash::make('actual-123')]));

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('startEditing')
            ->set('currentPassword', 'actual-123')
            ->set('state.name', 'Nombre nuevo')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertSet('editing', false);

        $this->assertSame('NOMBRE NUEVO', $user->fresh()->name);
        $this->assertTrue(Hash::check('actual-123', $user->fresh()->password));
    }

    public function test_password_can_be_changed_from_profile_editing(): void
    {
        $this->actingAs($user = User::factory()->create([
            'password' => Hash::make('Current-password-123'),
        ]));

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('currentPassword', 'Current-password-123')
            ->set('newPassword', 'New-password-456')
            ->set('newPasswordConfirmation', 'New-password-456')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('New-password-456', $user->fresh()->password));
    }

    public function test_profile_deletion_requires_the_exact_full_name_and_current_password(): void
    {
        $this->actingAs(User::factory()->create([
            'name' => 'María',
            'last_name' => 'López Pérez',
            'password' => Hash::make('Current-password-123'),
        ]));

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('deletionName', 'Otro nombre')
            ->set('deletionPassword', 'contraseña-incorrecta')
            ->call('deleteAccount')
            ->assertHasErrors(['deletionName', 'deletionPassword']);
    }

    public function test_another_users_profile_uses_the_shared_design_with_social_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $profile = User::factory()->create(['name' => 'Perfil', 'last_name' => 'Compartido']);

        $this->get(route('profiles.show', $profile))
            ->assertOk()
            ->assertSee('PERFIL COMPARTIDO')
            ->assertSee('Seguir')
            ->assertSee('Volver a mi perfil')
            ->assertSee('Enviar mensaje')
            ->assertDontSee('Descargar perfil PDF')
            ->assertDontSee('Regresar')
            ->assertDontSee('Imprimir')
            ->assertDontSee('Editar perfil')
            ->assertDontSee('Sesiones y dispositivos');
    }

    public function test_the_own_profile_has_no_back_or_print_buttons_and_offers_the_pdf_download(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->assertSee('Editar perfil')
            ->assertSee('Descargar perfil PDF')
            ->assertDontSee('Regresar')
            ->assertDontSee('Imprimir');
    }

    public function test_the_pdf_download_link_is_hidden_while_editing(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->assertSee('Descargar perfil PDF')
            ->call('startEditing')
            ->assertDontSee('Descargar perfil PDF')
            ->call('cancelEditing')
            ->assertSee('Descargar perfil PDF');
    }

    public function test_the_birth_year_can_be_saved_shown_with_the_age_and_cleared(): void
    {
        $this->actingAs($user = User::factory()->create());
        $year = now()->year - 30;

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('startEditing')
            ->set('state.birth_year', (string) $year)
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertSee((string) $year)
            ->assertSee('30 años');

        $this->assertSame($year, (int) $user->fresh()->birth_year);

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('startEditing')
            ->set('state.birth_year', '')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->birth_year);
    }

    public function test_an_invalid_birth_year_is_rejected_with_an_explanation(): void
    {
        $this->actingAs($user = User::factory()->create());

        foreach (['1850', (string) (now()->year + 1), (string) (now()->year - 5), 'abcd'] as $invalid) {
            Livewire::test(UpdateProfileInformationForm::class)
                ->call('startEditing')
                ->set('state.birth_year', $invalid)
                ->call('updateProfileInformation')
                ->assertHasErrors(['birth_year'])
                ->assertSet('editing', true);
        }

        $this->assertNull($user->fresh()->birth_year);
    }

    public function test_only_the_own_profile_sheet_pdf_can_be_downloaded(): void
    {
        $this->actingAs($user = User::factory()->create(['name' => 'Marina', 'last_name' => 'Manrique', 'birth_year' => 1998]));
        $other = User::factory()->create(['name' => 'Perfil', 'last_name' => 'Ajeno']);

        Livewire::test(UpdateProfileInformationForm::class)
            ->call('downloadPdf')
            ->assertFileDownloaded('PF-'.now(config('support.timezone'))->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT).'-marina-manrique.pdf');

        // La ficha de otra persona no se puede descargar
        Livewire::test(UpdateProfileInformationForm::class, ['user' => $other])
            ->call('downloadPdf')
            ->assertForbidden();
    }
}
