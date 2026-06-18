<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

class Header extends Component
{
    public $title;
    public $subtitle;

    public function mount($title = null, $subtitle = null)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    #[On('profile-updated')]
    public function refreshProfile(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.layout.header');
    }
}
