<?php

namespace Tests\Feature;

use App\Livewire\Profile\PeopleSidebar;
use App\Livewire\Profile\UpdateProfileInformationForm;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileSocialConnectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_stop_following_another_profile(): void
    {
        $this->actingAs($user = User::factory()->create());
        $other = User::factory()->create();

        Livewire::test(PeopleSidebar::class)->call('follow', $other->id);

        $this->assertTrue($user->following()->whereKey($other->id)->exists());
        $this->assertSame(Friendship::PENDING, Friendship::firstOrFail()->status);

        Livewire::test(UpdateProfileInformationForm::class, ['user' => $other])->call('removeFriendship');

        $this->assertFalse($user->following()->whereKey($other->id)->exists());
        $this->assertSame(0, Friendship::count());
    }

    public function test_friend_request_can_be_sent_and_accepted(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        $this->actingAs($requester);
        Livewire::test(PeopleSidebar::class)->call('follow', $addressee->id);

        $friendship = Friendship::firstOrFail();
        $this->assertSame(Friendship::PENDING, $friendship->status);
        $this->assertSame('Nueva solicitud de seguimiento', $addressee->fresh()->notifications()->first()->data['title']);

        $this->actingAs($addressee);
        Livewire::test(PeopleSidebar::class)->call('accept', $friendship->id);

        $this->assertSame(Friendship::ACCEPTED, $friendship->fresh()->status);
        $this->assertTrue($addressee->friends()->whereKey($requester->id)->exists());
        $this->assertTrue($requester->following()->whereKey($addressee->id)->exists());
        $this->assertTrue($addressee->following()->whereKey($requester->id)->exists());
    }

    public function test_user_cannot_create_social_connection_with_self(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(PeopleSidebar::class)
            ->call('follow', $user->id)
            ->assertStatus(422);
    }

    public function test_the_people_directory_page_no_longer_exists(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/perfiles')->assertNotFound();
    }

    public function test_clickable_people_profiles_are_read_only(): void
    {
        $viewer = User::factory()->create();
        $profile = User::factory()->create(['name' => 'Perfil Ajeno']);

        $this->actingAs($viewer)
            ->get(route('profiles.show', $profile))
            ->assertOk()
            ->assertSeeText('PERFIL AJENO')
            ->assertDontSeeText('Editar perfil')
            ->assertDontSee('wire:submit="updateProfileInformation"', false);
    }
}
