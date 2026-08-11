<?php

namespace App\Livewire\Profile;

use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as JetstreamUpdateProfileInformationForm;

class UpdateProfileInformationForm extends JetstreamUpdateProfileInformationForm
{
    public function mount(): void
    {
        parent::mount();

        $this->state['last_name'] = auth()->user()->last_name ?? '';
    }
}
