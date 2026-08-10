<?php

namespace App\Livewire\Support;

use App\Models\SupportChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class TicketChat extends Component
{
    public string $message = '';

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $todayLabel = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        [$start] = $this->dayBounds();
        SupportChatMessage::query()->where('created_at', '<', $start)->delete();

        $this->todayLabel = Carbon::now($this->timezone())->translatedFormat('l d \d\e F');
        $this->refreshMessages();
    }

    public function sendMessage(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $this->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Escribe un mensaje antes de enviarlo.',
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
        ]);

        $rateKey = 'support-chat:'.$user->getAuthIdentifier();
        if (RateLimiter::tooManyAttempts($rateKey, 12)) {
            $this->addError('message', 'Espera unos segundos antes de enviar más mensajes.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        SupportChatMessage::create([
            'user_id' => $user->getAuthIdentifier(),
            'message' => trim($validated['message']),
        ]);

        $this->reset('message');
        $this->refreshMessages();
        $this->dispatch('support-message-sent');
    }

    public function refreshMessages(): void
    {
        [$start, $end] = $this->dayBounds();
        $currentUserId = (int) auth()->id();

        $this->messages = SupportChatMessage::query()
            ->with('user:id,name,last_name,email')
            ->whereBetween('created_at', [$start, $end])
            ->oldest('created_at')
            ->oldest('id')
            ->get()
            ->map(fn (SupportChatMessage $message): array => [
                'id' => $message->id,
                'name' => trim(($message->user?->name ?? 'Usuario').' '.($message->user?->last_name ?? '')),
                'email' => $message->user?->email ?? 'Correo no disponible',
                'message' => $message->message,
                'time' => $message->created_at->timezone($this->timezone())->format('H:i'),
                'is_mine' => (int) $message->user_id === $currentUserId,
            ])
            ->all();

        $this->dispatch('support-messages-refreshed');
    }

    public function render(): View
    {
        return view('livewire.support.ticket-chat')->layout('layouts.app');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dayBounds(): array
    {
        $now = Carbon::now($this->timezone());

        return [
            $now->copy()->startOfDay()->utc(),
            $now->copy()->endOfDay()->utc(),
        ];
    }

    private function timezone(): string
    {
        return (string) config('support.timezone', 'America/Mexico_City');
    }
}
