<div>
    <x-slot name="header">
        <h1 class="font-bold text-xl md:text-2xl lg:text-3xl text-gray-900 dark:text-white">Settings</h1>
        <p class="text-md text-gray-500 hidden lg:block">Manage your account and preferences</p>
    </x-slot>

    <section class="px-3 sm:px-5 lg:px-10 pb-10">
        <div class="max-w-6xl mx-auto">
            <div class="space-y-8 max-w-[1100px] mx-auto">

                <!-- PROFILE -->
                <section id="profileSettings" class="settings-section scroll-mt-28 bg-white/90 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-7 shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-user text-indigo-500"></i>
                        </span>
                        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Profile Settings</h3>
                    </div>

                    <p class="text-gray-500 dark:text-gray-400 mb-6">Update your personal information</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <input value="{{ auth()->user()->name }}" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Full Name">
                        <input value="{{ auth()->user()->email }}" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Email">
                        <input value="{{ auth()->user()->phone ?? '' }}" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="WhatsApp Number">
                        <input class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Country Code">
                        <input class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition md:col-span-2" placeholder="Country Name">
                    </div>

                    <div class="flex gap-3 justify-end mt-6">
                        <button
                            type="button"
                            class="h-12 min-w-[140px] px-6 rounded-xl border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 flex items-center justify-center text-md font-semibold transition hover:bg-gray-100 dark:hover:bg-white/10">
                            Reset
                        </button>

                        <button
                            type="button"
                            class="h-12 min-w-[140px] px-6 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white flex items-center justify-center text-md font-semibold shadow-md hover:scale-[1.02] transition">
                            Save Changes
                        </button>
                    </div>
                </section>

                <!-- APPEARANCE -->
                <section id="appearanceSettings" class="settings-section scroll-mt-28 bg-white/90 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-7 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-palette text-indigo-500"></i>
                        </span>
                        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Appearance Settings</h3>
                    </div>

                    <p class="text-gray-500 dark:text-gray-400 mt-2 mb-7">
                        Customize how the interface looks and feels
                    </p>

                    <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                        <div>
                            <h4 class="font-bold text-lg text-gray-900 dark:text-white">Dark Mode</h4>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Toggle between light and dark theme</p>
                        </div>

                        <!-- We use Alpine.js here to toggle dark mode logic based on the user's local script if needed -->
                        <label x-data="{ isDark: document.documentElement.classList.contains('dark') }" class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" x-model="isDark" @change="isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.setItem('theme', isDark ? 'dark' : 'light')" class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-300 dark:bg-white/15 rounded-full peer-checked:bg-indigo-500 after:content-[''] after:absolute after:top-1 after:left-1 after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                    <div class="my-8 border-t border-gray-200 dark:border-white/10"></div>

                    <div>
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white">Theme Preference</h4>
                        <p class="text-gray-500 dark:text-gray-400 mb-5 text-sm">Choose your preferred theme mode</p>

                        <div class="space-y-4" x-data="{ theme: localStorage.getItem('theme') || 'system' }">
                            <button @click="theme = 'system'; localStorage.removeItem('theme'); if(window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark') } else { document.documentElement.classList.remove('dark') }" :class="{'border-indigo-400 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10': theme === 'system', 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5': theme !== 'system'}" class="w-full flex items-center gap-4 p-5 rounded-2xl border text-left transition hover:border-indigo-400 text-gray-900 dark:text-white">
                                <span :class="{'bg-indigo-500 border-indigo-500 text-white': theme === 'system', 'border-gray-400 text-transparent': theme !== 'system'}" class="w-5 h-5 rounded-full border flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-check" :class="{'hidden': theme !== 'system'}"></i>
                                </span>
                                <span class="w-11 h-11 rounded-xl bg-white dark:bg-white/10 flex items-center justify-center shadow-sm">
                                    <i class="fa-solid fa-desktop text-indigo-500"></i>
                                </span>
                                <span>
                                    <span class="block font-bold">System</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">Use system preference</span>
                                </span>
                            </button>

                            <button @click="theme = 'light'; localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark')" :class="{'border-indigo-400 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10': theme === 'light', 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5': theme !== 'light'}" class="w-full flex items-center gap-4 p-5 rounded-2xl border text-left transition hover:border-indigo-400 text-gray-900 dark:text-white">
                                <span :class="{'bg-indigo-500 border-indigo-500 text-white': theme === 'light', 'border-gray-400 text-transparent': theme !== 'light'}" class="w-5 h-5 rounded-full border flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-check" :class="{'hidden': theme !== 'light'}"></i>
                                </span>
                                <span class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-white/10 flex items-center justify-center shadow-sm">
                                    <i class="fa-solid fa-sun text-amber-500"></i>
                                </span>
                                <span>
                                    <span class="block font-bold">Light</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">Always use light theme</span>
                                </span>
                            </button>

                            <button @click="theme = 'dark'; localStorage.setItem('theme', 'dark'); document.documentElement.classList.add('dark')" :class="{'border-indigo-400 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10': theme === 'dark', 'border-gray-200 dark:border-white/10 bg-white dark:bg-white/5': theme !== 'dark'}" class="w-full flex items-center gap-4 p-5 rounded-2xl border text-left transition hover:border-indigo-400 text-gray-900 dark:text-white">
                                <span :class="{'bg-indigo-500 border-indigo-500 text-white': theme === 'dark', 'border-gray-400 text-transparent': theme !== 'dark'}" class="w-5 h-5 rounded-full border flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-check" :class="{'hidden': theme !== 'dark'}"></i>
                                </span>
                                <span class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-white/10 flex items-center justify-center shadow-sm">
                                    <i class="fa-solid fa-moon text-indigo-500"></i>
                                </span>
                                <span>
                                    <span class="block font-bold">Dark</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">Always use dark theme</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- NOTIFICATIONS -->
                <section id="notificationsSettings" class="settings-section scroll-mt-28 bg-white/90 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-7 shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-bell text-sky-500"></i>
                        </span>
                        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Notification Settings</h3>
                    </div>

                    <p class="text-gray-500 dark:text-gray-400 mb-6">Choose how you want to receive updates</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">Email Notifications</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Receive account and job updates by email</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 accent-indigo-500 shrink-0">
                        </div>

                        <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">Push Notifications</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Get instant alerts inside the platform</p>
                            </div>
                            <input type="checkbox" class="w-5 h-5 accent-indigo-500 shrink-0">
                        </div>
                    </div>
                </section>

                <!-- SECURITY -->
                <section id="securitySettings" class="settings-section scroll-mt-28 bg-white/90 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-7 shadow-sm">
                    <div class="flex items-start gap-3 mb-1">
                        <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-indigo-500"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white">Security Settings</h3>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your password and security preferences</p>
                        </div>
                    </div>

                    <div class="mt-7">
                        <h4 class="font-bold text-base mb-4 text-gray-900 dark:text-white">Change Password</h4>

                        <div class="space-y-5">
                            <div>
                                <label for="currentPassword" class="block mb-2 font-semibold text-sm text-gray-900 dark:text-white">Current Password</label>
                                <input id="currentPassword" type="password" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Enter current password">
                            </div>

                            <div>
                                <label for="newPassword" class="block mb-2 font-semibold text-sm text-gray-900 dark:text-white">New Password</label>
                                <input id="newPassword" type="password" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Enter new password">
                            </div>

                            <div>
                                <label for="confirmPassword" class="block mb-2 font-semibold text-sm text-gray-900 dark:text-white">Confirm Password</label>
                                <input id="confirmPassword" type="password" class="w-full h-12 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 text-sm text-gray-800 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Confirm new password">
                            </div>
                        </div>

                        <button class="mt-5 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold shadow-md hover:scale-[1.01] transition">
                            Update Password
                        </button>
                    </div>

                    <div class="my-8 border-t border-gray-200 dark:border-white/10"></div>

                    <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                        <div>
                            <h4 class="font-bold text-base text-gray-900 dark:text-white">Two-Factor Authentication</h4>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Add an extra layer of security to your account</p>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-200 dark:bg-white/15 rounded-full peer-checked:bg-indigo-500 after:content-[''] after:absolute after:top-1 after:left-1 after:w-5 after:h-5 after:bg-white after:rounded-full after:shadow after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                    <div class="my-8 border-t border-gray-200 dark:border-white/10"></div>

                    <div>
                        <h4 class="font-bold text-base text-gray-900 dark:text-white">Session Management</h4>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 mb-5">Sign out from all devices except this one</p>

                        <button class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-red-300 bg-red-50 text-red-500 font-semibold hover:bg-red-100 dark:bg-red-500/10 dark:border-red-500/30 dark:hover:bg-red-500/20 transition">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Logout from All Devices
                        </button>
                    </div>
                </section>

                <!-- BILLING -->
                <section id="billingSettings" class="settings-section scroll-mt-28 bg-white/90 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-3xl p-6 md:p-7 shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-credit-card text-amber-500"></i>
                        </span>
                        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Billing & Plan</h3>
                    </div>

                    <p class="text-gray-500 dark:text-gray-400 mb-6">Manage credits and subscription</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 p-5">
                            <p class="text-gray-500 dark:text-gray-400">Current Plan</p>
                            <h4 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ auth()->user()->plan->name ?? 'Free Plan' }}</h4>
                        </div>

                        <div class="rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 p-5">
                            <p class="text-gray-500 dark:text-gray-400">Credits Balance</p>
                            <h4 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ number_format(auth()->user()->credits) }} Credits</h4>
                        </div>
                    </div>

                    <button class="mt-6 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold shadow-md hover:scale-[1.02] transition">
                        Upgrade Plan
                    </button>
                </section>

            </div>
        </div>
    </section>
</div>
