<div class="contents">
    <div class="flex-1 text-center lg:text-left mb-10 lg:mb-0 px-4 sm:px-6 md:px-8 lg:pr-12">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-4 lg:-mt-[105px] bg-gradient-to-r from-[#B58BF6] via-[#6366f1] to-[#2ACDF0] dark:from-[#B58BF6] dark:to-[#2ACDF0] bg-clip-text text-transparent transition-colors duration-500">
            EduTech Platform
        </h1>
        <h2 class="text-xl sm:text-2xl font-semibold mb-4">Start your learning journey</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed max-w-md mx-auto lg:mx-0 transition-colors duration-500">
            Join thousands of learners transforming their skills with our cutting-edge education tools and resources.
        </p>
        <ul class="space-y-3 text-gray-600 dark:text-gray-300 text-base">
            <li class="flex items-center justify-center lg:justify-start gap-2">
                <i class="fa-solid fa-check-circle text-green-500 dark:text-green-400 text-lg"></i>
                <span>Free to start</span>
            </li>
            <li class="flex items-center justify-center lg:justify-start gap-2">
                <i class="fa-solid fa-check-circle text-green-500 dark:text-green-400 text-lg"></i>
                <span>No credit card required</span>
            </li>
        </ul>
    </div>

    <div class="flex-1 w-full max-w-md px-4 sm:px-6 md:px-0" x-data="{ showPassword: false }">
        <div class="relative backdrop-blur-3xl bg-white/50 dark:bg-white/10 rounded-2xl p-6 sm:p-8 md:p-10 border border-gray-200 dark:border-white/5 transition-all duration-700 ease-in-out">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-white/20 to-white/40 dark:via-[#060b21]/10 dark:to-[#060b21]/40 rounded-2xl pointer-events-none transition-all duration-700"></div>
            
            <form wire:submit="login" class="space-y-5 relative z-10">
                <h2 class="text-2xl font-semibold mb-4">Welcome back</h2>
                
                <div>
                    <label class="flex items-center gap-2 text-lg mb-1">
                        <i class="fa-regular fa-envelope text-blue-500 dark:text-blue-400"></i> Email Address
                    </label>
                    <input type="text" wire:model="email" placeholder="you@example.com" class="w-full py-2.5 bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 pb-4 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition"/>
                    @error('email') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-lg mb-1">
                        <i class="fa-solid fa-lock text-blue-500 dark:text-blue-400"></i> Password
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="password" placeholder="........" class="w-full py-2.5 bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white placeholder:text-[40px] px-4 pb-4 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition" />
                        <i @click="showPassword = !showPassword" class="fa-regular absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </div>
                    @error('password') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] text-white py-2.5 mt-4 pb-3 text-lg rounded-lg font-semibold transition-all duration-200 shadow-lg relative disabled:opacity-50">
                    <span wire:loading.remove>Sign in</span>
                    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Checking...</span>
                </button>

                <p class="text-center text-gray-600 dark:text-gray-400 text-sm mt-4">
                    Don't have an account?
                    <a href="{{ route('register.view') }}" wire:navigate class="text-blue-500 dark:text-blue-400 hover:underline">Sign Up</a>
                </p>
            </form>
        </div>
    </div>

    @if (session('auth_required'))
        <script>
            (() => {
                const showAuthRequiredToast = () => {
                    if (window.showToast) {
                        window.showToast(@json(session('auth_required')), 'warning');
                    }
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', showAuthRequiredToast, { once: true });
                } else {
                    showAuthRequiredToast();
                }
            })();
        </script>
    @endif
</div>
