<x-layouts.app>
    <div class="max-w-6xl mx-auto pb-10" x-data="dashboard()">
        <div class="rounded-3xl border border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur-2xl overflow-hidden">
            <!-- Cover -->
            <div class="relative h-28 sm:h-36 md:h-44 lg:h-48 w-full overflow-hidden bg-gradient-to-r from-indigo-200/70 via-purple-200/70 to-sky-200/70 dark:from-indigo-500/20 dark:via-purple-500/20 dark:to-sky-500/20">
                <div class="absolute right-3 top-3 sm:right-5 sm:top-5 flex items-center gap-2">
                    <button class="inline-flex items-center gap-2 dark:text-white text-gray-600 px-3 py-2 rounded-xl text-sm font-semibold bg-white/80 dark:bg-white/15 border border-white/10 hover:bg-white dark:hover:bg-white/15 transition">
                        <i class="fa-solid fa-image"></i> Change Cover
                    </button>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="px-4 sm:px-6 lg:px-7 pb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="-mt-9 sm:-mt-10 flex items-start gap-4">
                        <div class="relative group">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-r from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold text-3xl sm:text-4xl border-4 border-white dark:border-[#060b21] shadow-lg overflow-hidden relative">
                                <span>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                            </div>
                        </div>

                        <div class="pt-9 sm:pt-10">
                            <h2 class="text-2xl sm:text-3xl font-bold dark:text-white text-gray-900">{{ auth()->user()->name }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>

                            <div class="mt-3 flex items-center gap-3">
                                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium bg-green-500/10 text-green-500 dark:text-green-400 border border-green-500/20">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span> Active
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 lg:pt-8">
                        <button @click="logout" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 font-semibold transition border border-red-500/20">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-coins dark:text-amber-500 text-amber-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Credits Balance</p>
                            <div class="flex items-end gap-3">
                                <p class="text-2xl font-bold dark:text-white">250</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-briefcase dark:text-indigo-500 text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Jobs</p>
                            <p class="text-2xl font-bold dark:text-white">12</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white/60 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-gray-200/60 dark:border-white/10 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold dark:text-white">Personal Info</h3>
                </div>
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center">
                            <i class="fa-solid fa-user dark:text-purple-300 text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Full Name</p>
                            <p class="font-semibold dark:text-white">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center">
                            <i class="fa-solid fa-envelope text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Email</p>
                            <p class="font-semibold dark:text-white">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white/60 dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-gray-200/60 dark:border-white/10 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold dark:text-white">Account Info</h3>
                </div>
                <div class="space-y-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Role</p>
                        <p class="font-semibold uppercase text-indigo-500">{{ auth()->user()->role }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Joined</p>
                        <p class="font-semibold dark:text-white">{{ auth()->user()->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dashboard', () => ({
                logout() {
                    window.location.href = "{{ route('login') }}"; // Should ideally be a POST, placeholder based on existing code
                }
            }))
        })
    </script>
</x-layouts.app>
