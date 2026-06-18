@php
    $sidebarUser = auth()->user();
    $sidebarNameParts = preg_split('/\s+/', trim((string) ($sidebarUser->name ?? 'Guest')), -1, PREG_SPLIT_NO_EMPTY);
    $sidebarInitials = $sidebarNameParts
        ? strtoupper(substr($sidebarNameParts[0], 0, 1).(count($sidebarNameParts) > 1 ? substr(end($sidebarNameParts), 0, 1) : substr($sidebarNameParts[0], 1, 1)))
        : 'GU';
@endphp

<aside id="sidebar" x-cloak :class="$store.ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="sidebar fixed top-0 left-0 h-screen w-[260px] z-[999] transition-transform duration-300 backdrop-blur-3xl bg-white/50 dark:bg-white/5 border-r border-white/10 flex flex-col overflow-hidden">
    <div class="p-3 space-y-4 flex-1 flex flex-col min-h-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-r from-[#B58BF6] via-[#6366f1] to-[#2ACDF0] flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-bolt text-white text-xl"></i>
            </div>
            <div class="leading-tight">
                <h2 class="font-semibold text-2xl text-gray-900 dark:text-white">AI Tools</h2>
                <p class="text-md dark:text-gray-400 text-gray-500">Platform</p>
            </div>
        </div>

        <div x-data="{ open: false }">
            <button @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-3 bg-white/5 hover:bg-white/10 text-sm rounded-xl transition">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-folder-open dark:text-gray-300 text-gray-500"></i>
                    <span class="text-lg">My Project</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs opacity-70"></i>
            </button>

            <div x-show="open" x-transition class="mt-2 space-y-1 text-md">
                <a href="#" class="block px-4 py-2 rounded-lg hover:bg-white/10">Project One</a>
                <a href="#" class="block px-4 py-2 rounded-lg hover:bg-white/10">Project Two</a>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-2 text-lg">
            <a href="{{ route('dashboard') }}" wire:navigate @click="$store.ui.closeSidebar()" class="flex items-center gap-3 px-4 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-600/10 dark:bg-primary/10 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-all' }}">
                <i class="fa-solid fa-house {{ request()->routeIs('dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-300' }}"></i>
                Dashboard
            </a>
            <a href="{{ route('settings') }}" wire:navigate @click="$store.ui.closeSidebar()" class="flex items-center gap-3 px-4 py-2 rounded-lg {{ request()->routeIs('settings') ? 'bg-indigo-600/10 dark:bg-primary/10 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-all' }}">
                <i class="fa-solid fa-gear {{ request()->routeIs('settings') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-300' }}"></i>
                Settings
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-all">
                <i class="fa-regular fa-life-ring text-gray-500 dark:text-gray-300"></i>
                Support
            </a>
        </nav>

        <!-- History Section -->
        <div class="history dark:text-gray-400 pt-3 border-t dark:border-white/10 border-gray-300 flex flex-col max-h-[300px] overflow-y-auto pr-1">
            <div class="flex items-center justify-between mb-2 px-2">
                <p class="text-lg text-gray-500 dark:text-gray-400 mb-2">Recent History</p>
                <button class="text-sm dark:text-indigo-400 text-indigo-500 transition">Clear all</button>
            </div>

            <!-- Search -->
            <div class="mb-4 px-2">
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="Search chats..." 
                    class="w-full px-3 py-2 bg-white/50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-lg text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-gray-500"
                >
            </div>

            <ul class="space-y-2 text-sm overflow-auto px-2">
                @forelse($history as $chat)
                    <li>
                        <a 
                            href="{{ route('chat.session', $chat->id) }}" 
                            wire:navigate 
                            class="block px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 transition-all {{ request()->routeIs('chat.session') && request()->route('sessionId') == $chat->id ? 'bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white' : '' }}"
                        >
                            <div class="truncate">{{ $chat->title ?? 'New Chat' }}</div>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ $chat->updated_at->diffForHumans() }}</div>
                        </a>
                    </li>
                @empty
                    <li class="text-gray-500 opacity-70 px-2">No history yet</li>
                @endforelse
            </ul>

            <!-- Load More -->
            @if($history->count() >= $limit)
                <button 
                    wire:click="loadMore" 
                    class="w-full mt-4 py-2 text-xs text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 transition-colors text-center"
                >
                    Load More
                </button>
            @endif
        </div>
    </div>

    <div class="mt-auto border-t border-gray-300 dark:border-white/10 h-[80px] flex items-center justify-between px-4 hover:bg-gray-200 dark:bg-white/5 transition-colors">
        <a href="{{ route('profile') }}" wire:navigate @click="$store.ui.closeSidebar()">
            <div class="flex items-center gap-3 cursor-pointer hover:opacity-80 transition">
                <div class="w-10 h-10 rounded-full border border-[#60a5fa] flex items-center justify-center overflow-hidden bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold">
                    @if($sidebarUser?->avatar)
                        <img src="{{ $sidebarUser->avatar }}" alt="Profile" class="w-full h-full object-cover">
                    @else
                        {{ $sidebarInitials }}
                    @endif
                </div>
                <span class="text-gray-700 text-lg dark:text-gray-300">Profile</span>
            </div>
        </a>

        <button class="border border-gray-300 dark:border-white/10 cursor-pointer px-3 py-1 rounded-full text-gray-700 dark:text-gray-300 hover:bg-white/10 transition">
            Upgrade
        </button>
    </div>
</aside>
