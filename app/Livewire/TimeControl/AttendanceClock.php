<?php

namespace App\Livewire\TimeControl;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AttendanceClock extends Component
{
    public function mount(): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
    }

    public function render()
    {
        return view('livewire.time-control.attendance-clock')
            ->layout('layouts.app');
    }
}