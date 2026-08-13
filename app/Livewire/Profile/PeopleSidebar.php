<?php

namespace App\Livewire\Profile;

use App\Models\Friendship;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PeopleSidebar extends Component
{
    public string $search = '';

    public function follow(int $userId): void
    {
        abort_if($userId === Auth::id(), 422);
        if ($this->connectionWith($userId)->exists()) {
            return;
        }

        Friendship::create(['requester_id' => Auth::id(), 'addressee_id' => $userId, 'status' => Friendship::PENDING]);
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

    public function accept(int $friendshipId): void
    {
        $friendship = Friendship::query()->with('requester')
            ->whereKey($friendshipId)->where('addressee_id', Auth::id())
            ->where('status', Friendship::PENDING)->firstOrFail();

        $friendship->update(['status' => Friendship::ACCEPTED]);
        Auth::user()->following()->syncWithoutDetaching([$friendship->requester_id]);
        $friendship->requester->following()->syncWithoutDetaching([Auth::id()]);
    }

    private function connectionWith(int $userId)
    {
        return Friendship::query()->where(function ($query) use ($userId): void {
            $query->where(fn ($q) => $q->where('requester_id', Auth::id())->where('addressee_id', $userId))
                ->orWhere(fn ($q) => $q->where('requester_id', $userId)->where('addressee_id', Auth::id()));
        });
    }

    public function render()
    {
        $people = User::query()->whereKeyNot(Auth::id())
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('last_name', 'like', $term));
            })->orderBy('name')->orderBy('last_name')->get();

        $connections = Friendship::query()
            ->where(fn ($query) => $query->where('requester_id', Auth::id())->orWhere('addressee_id', Auth::id()))->get();

        return view('livewire.profile.people-sidebar', compact('people', 'connections'));
    }
}
