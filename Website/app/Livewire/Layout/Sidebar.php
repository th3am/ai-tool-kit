<?php

namespace App\Livewire\Layout;

use Livewire\Component;


use App\Models\ChatSession;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public $search = '';
    public $limit = 10;

    #[On('chat-session-updated')]
    public function refreshConfig() 
    {
        // Just by existing, this listener will cause a re-render when the event is dispatched
    }

    #[On('profile-updated')]
    public function refreshProfile(): void
    {
        //
    }

    public function loadMore()
    {
        $this->limit += 10;
    }

    public function getHistoryProperty()
    {
        return ChatSession::where('user_id', auth()->id())
            ->where('title', 'like', '%' . $this->search . '%')
            ->orderBy('updated_at', 'desc')
            ->take($this->limit)
            ->get();
    }

    public function render()
    {
        return view('livewire.layout.sidebar', [
            'history' => $this->history
        ]);
    }
}
