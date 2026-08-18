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
            ->set('state', [
                'name' => 'Test Name',
                'last_name' => 'Updated Last Name',
                'email' => 'test@example.com',
                'profile_description' => 'Descripción visible del perfil.',
                'instagram_url' => 'https://instagram.com/test',
                'facebook_url' => 'https://facebook.com/test',
            ])
            ->call('updateProfileInformation');

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('Updated Last Name', $user->fresh()->last_name);
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
            ->assertSee('Perfil Compartido')
            ->assertSee('Seguir')
            ->assertSee('Regresar a mi perfil')
            ->assertDontSee('Editar perfil')
            ->assertDontSee('Sesiones y dispositivos');
    }
}
