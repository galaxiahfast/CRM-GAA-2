<?php

namespace App\Livewire\TimeControl;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Página «Cronómetro y productividad»: primero el cronómetro y debajo, en la misma pantalla,
 * la productividad del usuario. La lógica sigue en RegistroActividades y MyProductivity.
 */
class ActivityHub extends Component
{
    public function mount(): void
    {
        if (auth()->user()->isAdmin()) {
            $this->redirect('/time/admin', navigate: false);
        }
    }

    public function render()
    {
        return view('livewire.time-control.activity-hub', [
            'showProductivity' => Gate::allows('operate-time-tracking') && Gate::allows('view-time-productivity'),
        ])->layout('layouts.app');
    }
}
