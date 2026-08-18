<?php

namespace App\Livewire\Profile;

use App\Models\Friendship;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as JetstreamUpdateProfileInformationForm;
use Livewire\Attributes\On;

class UpdateProfileInformationForm extends JetstreamUpdateProfileInformationForm
{
    public ?User $viewedUser = null;

    public bool $standalone = false;

    public bool $isOwnProfile = true;

    public bool $editing = false;

    public $cover;

    public bool $isOnline = false;

    public int $activityCount = 0;

    public int $friendCount = 0;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public string $deletionName = '';

    public string $deletionPassword = '';

    public function mount(?User $user = null): void
    {
        parent::mount();

        $this->viewedUser = $user?->exists ? $user : null;
        $this->standalone = $this->viewedUser !== null;
        $this->isOwnProfile = $this->viewedUser === null || $this->viewedUser->is(auth()->user());
        $this->state['last_name'] = auth()->user()->last_name ?? '';
        $profileUser = $this->viewedUser ?? auth()->user();
        $this->isOnline = DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $profileUser->id)
            ->where('last_activity', '>=', now()->subMinutes(2)->timestamp)
            ->exists();
        $this->activityCount = $profileUser->timeEntries()->count();
        $this->friendCount = $profileUser->friends()->count();
    }

    public function getUserProperty(): User
    {
        return $this->viewedUser ?? Auth::user();
    }

    public function getFriendshipProperty(): ?Friendship
    {
        if ($this->isOwnProfile) {
            return null;
        }

        return $this->friendshipQuery($this->getUserProperty()->id)->first();
    }

    #[On('friendship-updated')]
    public function refreshFriendships(): void
    {
        $this->friendCount = $this->getUserProperty()->friends()->count();
    }

    public function requestFriendship(): void
    {
        abort_if($this->isOwnProfile, 422);

        if ($this->friendship) {
            return;
        }

        $profileUser = $this->getUserProperty();

        Friendship::create([
            'requester_id' => Auth::id(),
            'addressee_id' => $profileUser->id,
            'status' => Friendship::PENDING,
        ]);
        Auth::user()->following()->syncWithoutDetaching([$profileUser->id]);

        $sender = Auth::user();
        app(SystemNotificationService::class)->sendToUser($profileUser, [
            'category' => 'social',
            'severity' => 'info',
            'title' => 'Nueva solicitud de seguimiento',
            'message' => trim($sender->name.' '.$sender->last_name).' quiere seguirte. Puedes aceptar la solicitud desde tu perfil.',
            'action_url' => route('profile.show'),
            'context' => ['sender_id' => $sender->id, 'friendship_status' => Friendship::PENDING],
        ]);
    }

    public function acceptFriendship(): void
    {
        $profileUser = $this->getUserProperty();
        $friendship = $this->friendshipQuery($profileUser->id)
            ->where('addressee_id', Auth::id())
            ->where('status', Friendship::PENDING)
            ->firstOrFail();

        $friendship->update(['status' => Friendship::ACCEPTED]);
        Auth::user()->following()->syncWithoutDetaching([$profileUser->id]);
        $profileUser->following()->syncWithoutDetaching([Auth::id()]);
    }

    public function removeFriendship(): void
    {
        abort_if($this->isOwnProfile, 422);
        $profileUser = $this->getUserProperty();
        $this->friendshipQuery($profileUser->id)->delete();
        Auth::user()->following()->detach($profileUser->id);
        $profileUser->following()->detach(Auth::id());
    }

    private function friendshipQuery(int $userId)
    {
        return Friendship::query()->where(function ($query) use ($userId): void {
            $query->where(fn ($q) => $q->where('requester_id', Auth::id())->where('addressee_id', $userId))
                ->orWhere(fn ($q) => $q->where('requester_id', $userId)->where('addressee_id', Auth::id()));
        });
    }

    public function startEditing(): void
    {
        abort_unless($this->isOwnProfile, 403);
        $this->resetSecurityFields();
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->reset(['photo', 'cover']);
        $this->resetSecurityFields();
        $this->resetErrorBag();
        $this->mount();
        $this->editing = false;
    }

    public function updateProfileInformation(UpdatesUserProfileInformation $updater): void
    {
        $this->resetErrorBag();
        $changingPassword = collect([$this->currentPassword, $this->newPassword, $this->newPasswordConfirmation])
            ->contains(fn ($value) => trim((string) $value) !== '');

        $rules = [
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
        ];

        if ($changingPassword) {
            $rules += [
                'currentPassword' => ['required', 'current_password:web'],
                'newPassword' => ['required', Password::defaults(), 'same:newPasswordConfirmation'],
                'newPasswordConfirmation' => ['required'],
            ];
        }

        $this->validate($rules, [
            'currentPassword.current_password' => 'La contraseña actual no es correcta.',
            'newPassword.same' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $updater->update(
            Auth::user(),
            $this->photo ? array_merge($this->state, ['photo' => $this->photo]) : $this->state
        );

        if ($this->cover) {
            $user = Auth::user()->fresh();

            if ($user->profile_cover_path) {
                Storage::disk('public')->delete($user->profile_cover_path);
            }

            $user->forceFill([
                'profile_cover_path' => $this->cover->storePublicly('profile-covers', 'public'),
            ])->save();
        }

        if ($changingPassword) {
            Auth::user()->forceFill(['password' => Hash::make($this->newPassword)])->save();
            $this->dispatch('password-updated');
        }

        $this->reset(['photo', 'cover']);
        $this->resetSecurityFields();
        Auth::user()->refresh();
        $this->mount();
        $this->editing = false;
        $this->dispatch('saved');
        $this->dispatch('refresh-navigation-menu');
    }

    public function deleteProfileCover(): void
    {
        $user = Auth::user();

        if ($user->profile_cover_path) {
            Storage::disk('public')->delete($user->profile_cover_path);
            $user->forceFill(['profile_cover_path' => null])->save();
        }

        $user->refresh();
    }

    public function deleteAccount(DeletesUsers $deleter)
    {
        $user = Auth::user();
        $expectedName = trim($user->name.' '.$user->last_name);

        $this->validate([
            'deletionName' => ['required', function (string $attribute, mixed $value, \Closure $fail) use ($expectedName): void {
                if (mb_strtolower(trim((string) $value)) !== mb_strtolower($expectedName)) {
                    $fail('Escribe tu nombre completo exactamente como aparece en el perfil.');
                }
            }],
            'deletionPassword' => ['required', 'current_password:web'],
        ], [
            'deletionPassword.current_password' => 'La contraseña actual no es correcta.',
        ]);

        $deleter->delete($user);
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirectRoute('login', navigate: true);
    }

    private function resetSecurityFields(): void
    {
        $this->reset([
            'currentPassword',
            'newPassword',
            'newPasswordConfirmation',
            'deletionName',
            'deletionPassword',
        ]);
    }

    public function render()
    {
        $view = view('profile.update-profile-information-form');

        return $this->standalone ? $view->layout('layouts.app') : $view;
    }
}
