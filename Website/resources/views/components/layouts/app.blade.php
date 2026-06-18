<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="livewire-update-uri" data-update-uri="{{ url('livewire/update') }}">
    <title>{{ $title ?? 'AI Tools Dashboard' }}</title>
    
    <!-- Scripts & Styles -->
    <!-- <script>
        tailwind = {
            darkMode: 'class'
        }
    </script> -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class', // Add this line
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('ui', {
                darkMode: localStorage.getItem('theme')
                    ? localStorage.getItem('theme') === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches,
                sidebarOpen: false,
                sidebarWidth: 260,
                init() {
                    this.updateTheme();
                    this.syncSidebarLayout();
                    window.addEventListener('resize', () => this.syncSidebarLayout());
                    document.addEventListener('livewire:navigating', () => this.closeSidebar());
                    document.addEventListener('livewire:navigated', () => this.syncSidebarLayout());
                },
                toggleTheme() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
                    this.updateTheme();
                },
                updateTheme() {
                    document.documentElement.classList.toggle('dark', this.darkMode);
                },
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    this.syncSidebarLayout();
                },
                closeSidebar() {
                    this.sidebarOpen = false;
                    this.syncSidebarLayout();
                },
                syncSidebarLayout() {
                    requestAnimationFrame(() => {
                        const pageContent = document.getElementById('pageContent');
                        const mainHeaderBar = document.getElementById('mainHeaderBar');
                        const menuBtn = document.getElementById('menuBtn');
                        const isDesktop = window.innerWidth >= 1024;

                        if (menuBtn) {
                            menuBtn.style.left = this.sidebarOpen ? '220px' : '';
                        }

                        if (!pageContent || !mainHeaderBar) {
                            return;
                        }

                        if (this.sidebarOpen && isDesktop) {
                            pageContent.style.marginLeft = `${this.sidebarWidth}px`;
                            pageContent.style.width = `calc(100% - ${this.sidebarWidth}px)`;
                            mainHeaderBar.style.left = `${this.sidebarWidth}px`;
                            mainHeaderBar.style.width = `calc(100% - ${this.sidebarWidth}px)`;
                            return;
                        }

                        pageContent.style.marginLeft = '';
                        pageContent.style.width = '';
                        mainHeaderBar.style.left = '';
                        mainHeaderBar.style.width = '';
                    });
                }
            });
        });
    </script>
    

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap');
        
        * {
            font-family: 'Tajawal', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
        
        .bg-mesh {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
        }
        
        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        /* Livewire Transition */
        /*.transition-fade {
            transition: opacity 0.3s ease-in-out;
        }*/
    </style>

    @livewireStyles
</head>
<body class="relative font-sans w-full bg-gray-100 dark:bg-[#060b21] text-gray-800 dark:text-white transition-colors duration-500 m-0 p-0 overflow-auto overflow-x-hidden antialiased">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none">
        <!-- Livewire Toasts can be injected here -->
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" x-cloak x-show="$store.ui.sidebarOpen" @click="$store.ui.closeSidebar()" x-transition.opacity class="fixed inset-0 bg-black/30 z-[998] lg:hidden"></div>

    <!-- Sidebar Component -->
    <livewire:layout.sidebar />

    <!-- Main Content -->
    <main class="relative w-full min-h-screen md:col-span-1">
        <!-- Header Component -->
        <livewire:layout.header />

        <!-- Page Content -->
        <div id="pageContent" class="w-full transition-all duration-300">
            <div id="mainContent" class="main-content w-full h-auto pt-16 lg:pt-20 transition-transform duration-300">
                {{ $slot }}
            </div>
        </div>
    </main>

    @livewireScripts(['url' => url('livewire/livewire.js')])
</body>
</html>
