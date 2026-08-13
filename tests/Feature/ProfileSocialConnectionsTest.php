<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfileDirectory;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileSocialConnectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_another_profile(): void
    {
        $this->actingAs($user = User::factory()->create());
        $other = User::factory()->create();

        Livewire::test(ProfileDirectory::class)
            ->call('toggleFollow', $other->id);

        $this->assertTrue($user->following()->whereKey($other->id)->exists());

        Livewire::test(ProfileDirectory::class)
            ->call('toggleFollow', $other->id);

        $this->assertFalse($user->following()->whereKey($other->id)->exists());
    }

    public function test_friend_request_can_be_sent_and_accepted(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();

        $this->actingAs($requester);
        Livewire::test(ProfileDirectory::class)->call('requestFriendship', $addressee->id);

        $friendship = Friendship::firstOrFail();
        $this->assertSame(Friendship::PENDING, $friendship->status);
        $this->assertSame('Nueva solicitud de seguimiento', $addressee->fresh()->notifications()->first()->data['title']);

        $this->actingAs($addressee);
        Livewire::test(ProfileDirectory::class)->call('acceptFriendship', $friendship->id);

        $this->assertSame(Friendship::ACCEPTED, $friendship->fresh()->status);
        $this->assertTrue($addressee->friends()->whereKey($requester->id)->exists());
        $this->assertTrue($requester->following()->whereKey($addressee->id)->exists());
        $this->assertTrue($addressee->following()->whereKey($requester->id)->exists());
    }

    public function test_user_cannot_create_social_connection_with_self(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(ProfileDirectory::class)
            ->call('requestFriendship', $user->id)
            ->assertStatus(422);
    }

    public function test_clickable_people_profiles_are_read_only(): void
    {
        $viewer = User::factory()->create();
        $profile = User::factory()->create(['name' => 'Perfil Ajeno']);

        $this->actingAs($viewer)
            ->get(route('profiles.show', $profile))
            ->assertOk()
            ->assertSeeText('Perfil Ajeno')
            ->assertDontSeeText('Editar perfil')
            ->assertDontSee('wire:submit="updateProfileInformation"', false);
    }
}
