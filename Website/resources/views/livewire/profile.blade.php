<div>
    <section class="px-3 sm:px-5 lg:px-10 pb-10">
        <div class="max-w-6xl mx-auto">
            <div class="rounded-3xl border border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur-2xl overflow-hidden">
                <div class="relative">
                    <div class="relative h-28 sm:h-36 md:h-44 lg:h-48 w-full overflow-hidden bg-gradient-to-r from-indigo-200/70 via-purple-200/70 to-sky-200/70 dark:from-indigo-500/20 dark:via-purple-500/20 dark:to-sky-500/20">
                        @if($user->profile_cover)
                            <img src="{{ $user->profile_cover }}" alt="Profile cover" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                    </div>

                    <div class="absolute right-3 top-3 sm:right-5 sm:top-5 flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 dark:text-white text-gray-600 px-3 py-2 rounded-xl text-sm font-semibold bg-white/80 dark:bg-white/15 border border-white/10 hover:bg-white dark:hover:bg-white/15 transition cursor-pointer">
                            <i class="fa-solid fa-image"></i>
                            <span wire:loading.remove wire:target="coverUpload">Change Cover</span>
                            <span wire:loading wire:target="coverUpload">Uploading...</span>
                            <input wire:model="coverUpload" type="file" accept="image/*" class="hidden">
                        </label>

                        <button
                            type="button"
                            wire:click="removeCover"
                            wire:loading.attr="disabled"
                            class="w-9 h-9 rounded-xl bg-white/80 dark:bg-white/10 border border-white/10 hover:bg-white dark:hover:bg-white/15 transition flex items-center justify-center text-red-500 hover:text-red-600 disabled:opacity-50"
                            title="Remove cover"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="px-4 sm:px-6 lg:px-7 pb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="-mt-9 sm:-mt-10 flex items-start gap-4">
                            <div class="relative group">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-r from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold text-3xl sm:text-4xl border-4 border-white dark:border-[#060b21] shadow-lg overflow-hidden relative">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" class="w-full h-full object-cover" alt="Profile picture">
                                    @else
                                        <span>{{ $initials }}</span>
                                    @endif

                                    <div class="absolute inset-0 rounded-full flex items-center justify-center gap-2 bg-black/35 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition">
                                        <label class="w-7 h-7 rounded-xl bg-white/90 dark:bg-white/10 border border-white/20 hover:bg-white dark:hover:bg-white/15 transition flex items-center justify-center text-gray-700 dark:text-white cursor-pointer" title="Change avatar">
                                            <i class="fa-solid fa-camera text-sm dark:text-gray-300 text-gray-500"></i>
                                            <input wire:model="avatarUpload" type="file" accept="image/*" class="hidden">
                                        </label>

                                        <button
                                            type="button"
                                            wire:click="removeAvatar"
                                            wire:loading.attr="disabled"
                                            class="w-7 h-7 rounded-xl bg-white/90 dark:bg-white/10 border border-white/20 hover:bg-white dark:hover:bg-white/15 transition flex items-center justify-center text-red-500 hover:text-red-600 disabled:opacity-50"
                                            title="Remove avatar"
                                        >
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-green-500 flex items-center justify-center border-4 border-white dark:border-[#060b21]">
                                    <i class="fa-solid fa-check text-white text-xs"></i>
                                </span>
                            </div>

                            <div class="pt-9 sm:pt-10">
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                                <p class="text-gray-500 dark:text-gray-400">{{ $user->email ?? $user->whatsapp_number }}</p>

                                <div class="mt-3 flex items-center gap-3">
                                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium bg-green-500/10 text-green-500 dark:text-green-400 border border-green-500/20">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('avatarUpload')
                        <p class="mt-4 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('coverUpload')
                        <p class="mt-4 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-coins dark:text-amber-500 text-amber-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-500 dark:text-gray-400 text-md">Credits Balance</p>
                                <div class="flex items-end gap-3">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($user->credits ?? 0) }}</p>
                                    <a href="#" class="text-sm text-indigo-500 dark:text-indigo-400 mb-0.5">Top Up</a>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-briefcase dark:text-indigo-500 text-indigo-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-md">Jobs</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($jobsCount) }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-sky-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-cloud-arrow-up dark:text-sky-500 text-sky-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-md">Files Uploaded</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($filesUploaded) }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white/50 dark:bg-white/5 border border-gray-200/60 dark:border-white/10 shadow-sm p-4 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-clock dark:text-emerald-500 text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-md">Last Activity</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $lastActivity }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200/70 dark:border-white/10 pt-4">
                        <div class="flex flex-wrap items-center gap-6 px-1">
                            <button type="button" wire:click="setTab('overview')" class="tab {{ $activeTab === 'overview' ? 'active' : '' }}">Overview</button>
                            <button type="button" wire:click="setTab('recent')" class="tab {{ $activeTab === 'recent' ? 'active' : '' }}">Recent Jobs</button>
                        </div>
                    </div>

                    @if($activeTab === 'recent')
                        <div class="mt-6 bg-white dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-gray-200/60 dark:border-white/10 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Jobs</h3>
                                <span class="text-sm text-gray-500">{{ $recentJobs->count() }} shown</span>
                            </div>

                            <div class="max-h-[400px] overflow-y-auto pr-2 space-y-4">
                                @forelse($recentJobs as $job)
                                    @php
                                        $statusClasses = match($job->status) {
                                            'succeeded', 'completed' => 'bg-green-500/10 text-green-500 dark:text-green-400 border-green-500/20',
                                            'failed' => 'bg-red-500/10 text-red-500 dark:text-red-400 border-red-500/20',
                                            'cancelled' => 'bg-gray-500/10 text-gray-500 dark:text-gray-400 border-gray-500/20',
                                            default => 'bg-yellow-500/10 text-yellow-500 dark:text-yellow-400 border-yellow-500/20',
                                        };
                                        $topic = $job->params['topic'] ?? $job->chatSession?->title ?? 'Untitled job';
                                    @endphp
                                    <div class="rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 p-5">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-lg text-gray-900 dark:text-white">{{ $topic }}</p>
                                                <p class="text-sm text-gray-400 mt-1">{{ str_replace('_', ' ', ucfirst($job->tool_type)) }} · Job #{{ $job->id }} · {{ $job->created_at->diffForHumans() }}</p>
                                            </div>

                                            <span class="px-3 py-1 rounded-full text-sm border {{ $statusClasses }}">
                                                {{ ucfirst($job->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-400">No jobs found</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($activeTab === 'overview')
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-gray-200/60 dark:border-white/10 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Personal Info</h3>
                        </div>

                        <div class="space-y-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-user dark:text-purple-300 text-purple-400"></i>
                                </div>
                                <div>
                                    <p class="text-md text-gray-500 dark:text-gray-400">Full Name</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/15 dark:bg-emerald-500/10 flex items-center justify-center">
                                    <i class="fa-solid fa-envelope text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-md text-gray-500 dark:text-gray-400">Email</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $user->email ?? 'Not available' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-phone dark:text-emerald-300 text-emerald-500"></i>
                                </div>
                                <div>
                                    <p class="text-md text-gray-500 dark:text-gray-400">Phone</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ trim(($user->country_code ?? '').' '.($user->whatsapp_number ?? '')) ?: 'Not available' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-white/5 backdrop-blur-xl rounded-3xl border border-gray-200/60 dark:border-white/10 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Account Info</h3>
                        </div>

                        <div class="space-y-5">
                            <div class="flex items-center justify-between">
                                <p class="text-md text-gray-500 dark:text-gray-400">Joined</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y') }}</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-md text-gray-500 dark:text-gray-400">Plan</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $user->plan->name ?? 'Free Plan' }}</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-md text-gray-500 dark:text-gray-400">Status</p>
                                <div class="flex items-center gap-3">
                                    <span class="px-4 py-1.5 text-md rounded-full bg-green-500/10 text-green-700 dark:text-green-400 border border-green-500/20 font-medium">
                                        Active
                                    </span>

                                    <button type="button" class="px-4 py-2 rounded-xl text-md font-semibold dark:bg-white/10 bg-gray-100 text-red-600 hover:opacity-80 transition">
                                        Upgrade
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <style>
        .tab {
            position: relative;
            padding-bottom: 6px;
            color: #64748b;
            font-weight: 500;
            transition: 0.3s;
        }

        .tab:hover,
        .tab.active {
            color: #7c3aed;
        }

        .tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background: #7c3aed;
            border-radius: 2px;
        }
    </style>
</div>
