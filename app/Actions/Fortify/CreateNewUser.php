<?php

namespace App\Actions\Fortify;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // En el registro público la contraseña solo debe existir (sin largo mínimo ni confirmación);
            // el medidor de la pantalla de acceso orienta al usuario sobre su fortaleza.
            'password' => ['required', 'string', 'max:255'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $defaultRole = Role::query()->firstOrCreate(
            ['role' => 'Becario'],
            [
                'description' => 'Cuenta de becario registrada desde el portal',
                'permission_profile' => Role::PROFILE_AUXILIARY,
            ]
        );

        if (! $defaultRole->usesPermissionProfile(Role::PROFILE_AUXILIARY)) {
            $defaultRole->update(['permission_profile' => Role::PROFILE_AUXILIARY]);
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => $defaultRole->id,
        ]);
    }
}
