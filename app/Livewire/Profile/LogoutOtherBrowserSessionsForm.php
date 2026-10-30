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
        // Si las sesiones no se guardan en base de datos (por ejemplo, SESSION_DRIVER=file) no hay registro de dispositivos:
        // se muestra al menos el equipo desde el que se está viendo esta pantalla.
        if (config('session.driver') !== 'database') {
            return collect([(object) [
                'agent' => $this->createAgent((object) ['user_agent' => request()->userAgent()]),
                'ip_address' => request()->ip(),
                'is_current_device' => true,
                'last_active' => '',
                'id' => request()->hasSession() ? request()->session()->getId() : 'current-device',
            ]]);
        }

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
