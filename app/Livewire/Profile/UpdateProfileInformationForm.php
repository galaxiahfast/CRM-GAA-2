<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as JetstreamUpdateProfileInformationForm;

class UpdateProfileInformationForm extends JetstreamUpdateProfileInformationForm
{
    public bool $editing = false;

    public $cover;

    public bool $isOnline = false;

    public int $activityCount = 0;

    public int $friendCount = 0;

    public function mount(): void
    {
        parent::mount();

        $this->state['last_name'] = auth()->user()->last_name ?? '';
        $this->isOnline = DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', auth()->id())
            ->where('last_activity', '>=', now()->subMinutes(2)->timestamp)
            ->exists();
        $this->activityCount = auth()->user()->timeEntries()->count();
        $this->friendCount = auth()->user()->friends()->count();
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->reset(['photo', 'cover']);
        $this->resetErrorBag();
        $this->mount();
        $this->editing = false;
    }

    public function updateProfileInformation(UpdatesUserProfileInformation $updater): void
    {
        $this->resetErrorBag();
        $this->validate([
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
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

        $this->reset(['photo', 'cover']);
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
}
