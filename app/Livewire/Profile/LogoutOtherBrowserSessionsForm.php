<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm as JetstreamLogoutOtherBrowserSessionsForm;

class LogoutOtherBrowserSessionsForm extends JetstreamLogoutOtherBrowserSessionsForm
{
    public function logoutCurrentSession()
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirectRoute('login', navigate: true);
    }

    public function logoutSession(string $sessionId): void
    {
        if (config('session.driver') !== 'database' || $sessionId === request()->session()->getId()) {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->where('id', $sessionId)
            ->delete();

        $this->dispatch('loggedOut');
    }

    public function getSessionsProperty()
    {
        return parent::getSessionsProperty()->map(function ($session, $index) {
            $record = DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', Auth::id())
                ->orderBy('last_activity', 'desc')
                ->get()
                ->get($index);

            $session->id = $record?->id;

            return $session;
        });
    }
}
