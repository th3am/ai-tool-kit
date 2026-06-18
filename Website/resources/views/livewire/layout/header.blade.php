@php
    $headerUser = auth()->user();
    $headerNameParts = preg_split('/\s+/', trim((string) ($headerUser->name ?? 'Guest')), -1, PREG_SPLIT_NO_EMPTY);
    $headerInitials = $headerNameParts
        ? strtoupper(substr($headerNameParts[0], 0, 1).(count($headerNameParts) > 1 ? substr(end($headerNameParts), 0, 1) : substr($headerNameParts[0], 1, 1)))
        : 'GU';
@endphp

<header id="mainHeaderBar" class="fixed top-0 left-0 w-full lg:w-full h-16 lg:h-20 z-50 border-b justify-between border-gray-300 dark:border-white/10 flex backdrop-blur-xl bg-white/60 dark:bg-[#060b21]/60 transition-all duration-300">
    <button id="menuBtn" @click="$store.ui.toggleSidebar()" class="fixed top-4 left-4 z-[1000] text-xl w-9 h-9 lg:w-10 lg:h-10 rounded-lg text-gray-500 dark:text-gray-400 flex items-center justify-center transition-all duration-300">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div id="mainHeader" class="header w-full lg:w-[35%] pt-3 md:pt-4 lg:pt-4 px-4 ml-14">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title ?? 'AI Tools Dashboard' }}</h1>
        <p class="text-md text-gray-500 hidden lg:block">{{ $subtitle ?? 'Transform Your Content With AI' }}</p>
    </div>

    <div class="h-full flex justify-end">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-4">
                <div class="relative rounded-lg border hidden md:block border-gray-400 dark:border-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input
                        type="text"
                        placeholder="Search..."
                        class="pl-9 pr-4 py-1 md:py-2 w-10 md:w-40 lg:w-60 rounded-lg bg-white/5 text-md text-gray-800 dark:text-gray-200 placeholder-gray-500 outline-none border border-transparent focus:ring-1 focus:ring-indigo-500" />
                </div>

                <span class="flex items-center gap-2 px-4 py-3 md:py-0 lg:py-1.5 rounded-full text-sm font-medium bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20">
                    <i class="fa-solid fa-coins"></i>
                    <span class="text-sm font-medium hidden md:block md:text-[14px] lg:text-sm">{{ number_format((int) ($headerUser->credits ?? 0)) }} Credits</span>
                </span>
            </div>

            <div class="flex items-center gap-3 ml-[11px]">
                <button @click="$store.ui.toggleTheme()" class="w-10 h-10 rounded-lg bg-[#DBDDE9] dark:bg-white/5 flex items-center dark:hover:bg-white/15 hover:bg-gray-300 justify-center text-gray-500 dark:text-gray-300 transition">
                    <i x-show="$store.ui.darkMode" class="fa-solid fa-sun"></i>
                    <i x-show="!$store.ui.darkMode" class="fa-solid fa-moon"></i>
                </button>

                <button class="px-3 h-10 rounded-lg dark:bg-white/5 bg-[#DBDDE9] dark:hover:bg-white/15 hover:bg-gray-300 text-sm text-gray-600 dark:text-gray-300 font-medium transition">
                    EN
                </button>

                <button class="relative w-10 h-10 rounded-lg bg-[#DBDDE9] dark:bg-white/5 dark:hover:bg-white/15 hover:bg-gray-300 flex items-center justify-center text-gray-500 transition">
                    <i class="fa-regular fa-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500"></span>
                </button>

                <!-- User Avatar -->
                <div x-data="{ open: false }" class="relative ml-2">
                <button @click="open = !open" class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white font-semibold cursor-pointer overflow-hidden">
                        @if($headerUser?->avatar)
                            <img src="{{ $headerUser->avatar }}" alt="Profile" class="w-full h-full object-cover">
                        @else
                            {{ $headerInitials }}
                        @endif
                    </div>
                </button>
                
                <!-- Dropdown -->
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-48 backdrop-blur-3xl bg-white/60 dark:bg-[#060b21]/90 rounded-xl shadow-lg py-2 border border-gray-200 dark:border-white/10 z-50">
                    <a href="{{ route('profile') }}" wire:navigate @click="open = false; $store.ui.closeSidebar()" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-white/5">Sign Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</header>
