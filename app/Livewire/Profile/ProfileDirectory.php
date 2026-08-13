<?php

namespace App\Livewire\Profile;

use App\Models\Friendship;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProfileDirectory extends Component
{
    public ?User $profile = null;

    public string $search = '';

    public bool $profileIsOnline = false;

    public int $profileActivityCount = 0;

    public int $profileFriendCount = 0;

    public function mount(?User $user = null): void
    {
        $this->profile = $user?->load(['activeOrganizationalProfile.physicalArea', 'activeOrganizationalProfile.jobPosition', 'superiors', 'subordinates']);
        $this->profileIsOnline = $user !== null && DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes(2)->timestamp)
            ->exists();
        $this->profileActivityCount = $user?->timeEntries()->count() ?? 0;
        $this->profileFriendCount = $user?->friends()->count() ?? 0;
    }

    public function toggleFollow(int $userId): void
    {
        abort_if($userId === Auth::id(), 422);
        $user = User::findOrFail($userId);

        Auth::user()->following()->toggle($user->id);
    }

    public function requestFriendship(int $userId): void
    {
        abort_if($userId === Auth::id(), 422);

        $existing = $this->friendshipWith($userId);
        if ($existing) {
            return;
        }

        Friendship::create([
            'requester_id' => Auth::id(),
            'addressee_id' => $userId,
            'status' => Friendship::PENDING,
        ]);

        Auth::user()->following()->syncWithoutDetaching([$userId]);

        $sender = Auth::user();
        $recipient = User::findOrFail($userId);
        app(SystemNotificationService::class)->sendToUser($recipient, [
            'category' => 'social',
            'severity' => 'info',
            'title' => 'Nueva solicitud de seguimiento',
            'message' => trim($sender->name.' '.$sender->last_name).' quiere seguirte. Puedes aceptar la solicitud desde tu perfil.',
            'action_url' => route('profile.show'),
            'context' => ['sender_id' => $sender->id, 'friendship_status' => Friendship::PENDING],
        ]);
    }

    public function acceptFriendship(int $friendshipId): void
    {
        $friendship = Friendship::query()
            ->with('requester')
            ->whereKey($friendshipId)
            ->where('addressee_id', Auth::id())
            ->where('status', Friendship::PENDING)
            ->firstOrFail();

        $friendship->update(['status' => Friendship::ACCEPTED]);
        Auth::user()->following()->syncWithoutDetaching([$friendship->requester_id]);
        $friendship->requester->following()->syncWithoutDetaching([Auth::id()]);
    }

    public function removeFriendship(int $userId): void
    {
        $this->friendshipQuery($userId)->delete();
    }

    public function friendshipWith(int $userId): ?Friendship
    {
        return $this->friendshipQuery($userId)->first();
    }

    private function friendshipQuery(int $userId)
    {
        return Friendship::query()->where(function ($query) use ($userId): void {
            $query->where(fn ($q) => $q->where('requester_id', Auth::id())->where('addressee_id', $userId))
                ->orWhere(fn ($q) => $q->where('requester_id', $userId)->where('addressee_id', Auth::id()));
        });
    }

    public function render()
    {
        $users = User::query()
            ->with('activeOrganizationalProfile.physicalArea')
            ->whereKeyNot(Auth::id())
            ->when($this->search !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('last_name', 'like', $term));
            })
            ->orderBy('name')
            ->limit(40)
            ->get();

        $followingIds = Auth::user()->following()->pluck('users.id');
        $friendships = Friendship::query()
            ->with(['requester', 'addressee'])
            ->where(fn ($query) => $query->where('requester_id', Auth::id())->orWhere('addressee_id', Auth::id()))
            ->get();

        return view('livewire.profile.profile-directory', compact('users', 'followingIds', 'friendships'))
            ->layout('layouts.app');
    }
}
