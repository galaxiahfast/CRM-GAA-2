<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'profile_description' => ['nullable', 'string', 'max:500'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'last_name' => $input['last_name'] ?? null,
                'email' => $input['email'],
                'profile_description' => $this->nullableTrimmed($input['profile_description'] ?? null),
                'instagram_url' => $this->nullableTrimmed($input['instagram_url'] ?? null),
                'facebook_url' => $this->nullableTrimmed($input['facebook_url'] ?? null),
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'last_name' => $input['last_name'] ?? null,
            'email' => $input['email'],
            'email_verified_at' => null,
            'profile_description' => $this->nullableTrimmed($input['profile_description'] ?? null),
            'instagram_url' => $this->nullableTrimmed($input['instagram_url'] ?? null),
            'facebook_url' => $this->nullableTrimmed($input['facebook_url'] ?? null),
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
