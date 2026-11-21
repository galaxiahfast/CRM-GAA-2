<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DefaultAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $name, ?string $gender = null): User
    {
        return User::create(['name' => $name, 'last_name' => 'Prueba', 'email' => uniqid('p').'@datamid.test', 'password' => Hash::make('x'), 'gender' => $gender, 'role_id' => \App\Models\Role::firstOrCreate(['role' => 'Auxiliar'])->id]);
    }

    public function test_without_photo_the_avatar_is_an_illustration_chosen_by_the_name(): void
    {
        foreach (['María José' => 'mujer', 'José María' => 'hombre', 'Lizet' => 'mujer', 'Josué' => 'hombre', 'Alejandra' => 'mujer', 'Diego' => 'hombre'] as $name => $gender) {
            $user = $this->person($name);
            $this->assertSame($gender, $user->avatarGender(), $name);
            $this->assertStringContainsString('/img/avatars/'.$gender.'-', $user->avatarUrl());
            $this->assertSame($user->avatarUrl(), $user->profile_photo_url, 'Jetstream usa la misma ilustración');
        }
    }

    public function test_the_captured_gender_wins_over_the_name_and_the_illustration_does_not_change(): void
    {
        $user = $this->person('Andrea', 'hombre');

        $this->assertSame('hombre', $user->avatarGender());
        $this->assertSame($user->avatarUrl(), $user->fresh()->avatarUrl());
    }

    public function test_gender_is_optional_in_the_profile_like_the_birth_year(): void
    {
        $user = $this->person('Ana');
        $update = app(UpdateUserProfileInformation::class);

        $update->update($user, ['name' => 'Ana', 'email' => $user->email, 'gender' => 'mujer']);
        $this->assertSame('mujer', $user->fresh()->gender);

        $update->update($user->fresh(), ['name' => 'Ana', 'email' => $user->email, 'gender' => '']);
        $this->assertNull($user->fresh()->gender);

        $this->expectException(ValidationException::class);
        $update->update($user->fresh(), ['name' => 'Ana', 'email' => $user->email, 'gender' => 'otro']);
    }
}
