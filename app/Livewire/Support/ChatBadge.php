<?php

namespace App\Livewire\Support;

use App\Models\SupportChatMessage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatBadge extends Component
{
    #[On('support-chat-seen')]
    public function refreshBadge(): void
    {
        // Solo vuelve a renderizar.
    }

    public function render(): View
    {
        $user = auth()->user();
        $seenAt = $user?->support_chat_seen_at;

        $unread = $user ? SupportChatMessage::query()
            ->where('user_id', '!=', $user->id)
            ->when($seenAt, fn ($q) => $q->where('created_at', '>', $seenAt))
            ->count() : 0;

        return view('livewire.support.chat-badge', ['unread' => $unread]);
    }
}
