@extends('layouts.auth')

@section('content')
<div class="flex-1 text-center lg:text-left mb-10 lg:mb-0 px-4 sm:px-6 md:px-8 lg:pr-12">
    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-4 lg:-mt-[105px] bg-gradient-to-r from-[#B58BF6] via-[#6366f1] to-[#2ACDF0] dark:from-[#B58BF6] dark:to-[#2ACDF0] bg-clip-text text-transparent transition-colors duration-500">
        EduTech Platform
    </h1>
    <h2 class="text-xl sm:text-2xl font-semibold mb-4">Start your learning journey</h2>
    <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed max-w-md mx-auto lg:mx-0 transition-colors duration-500">
        Join thousands of learners transforming their skills with our cutting-edge education tools and resources.
    </p>
</div>

<div class="flex-1 w-full max-w-md px-4 sm:px-6 md:px-0" x-data="loginForm()">
    <div class="relative backdrop-blur-3xl bg-white/50 dark:bg-white/10 rounded-2xl p-6 sm:p-8 md:p-10 border border-gray-200 dark:border-white/5 transition-all duration-700 ease-in-out">
        <h2 class="text-2xl font-semibold mb-4">Welcome back</h2>

        <form @submit.prevent="submit" class="space-y-5 relative z-10">
            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-regular fa-envelope text-blue-500 dark:text-blue-400"></i> Email or WhatsApp
                </label>
                <input type="text" x-model="form.email" required placeholder="you@example.com or +123..." class="w-full py-2.5 bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 pb-4 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition"/>
            </div>

            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-solid fa-lock text-blue-500 dark:text-blue-400"></i> Password
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" x-model="form.password" required placeholder="........" class="w-full py-2.5 bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 pb-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition" />
                    <i @click="showPassword = !showPassword" class="fa-regular absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </div>
            </div>

            <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] text-white py-2.5 mt-4 pb-3 text-lg rounded-lg font-semibold transition-all duration-200 shadow-lg disabled:opacity-50">
                <span x-show="!loading">Sign In</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin"></i> Checking...</span>
            </button>

            <p class="text-center text-gray-600 dark:text-gray-400 text-sm">
                Don't have an account?
                <a href="{{ route('register.view') }}" class="text-blue-500 dark:text-blue-400 hover:underline">Sign Up</a>
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

<script>
    function loginForm() {
        return {
            form: {
                email: '',
                password: ''
            },
            loading: false,
            showPassword: false,
            submit() {
                this.loading = true;
                axios.post('{{ route('login.post') }}', this.form)
                    .then(response => {
                        // Success Logic
                        // 1. Direct Redirect (e.g. Admin Bypass)
                        if (response.data.redirect_url && !response.data.require_otp) {
                            window.showToast(response.data.message, 'success');
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1000);
                            return;
                        }

                        // 2. OTP Required
                        if (response.data.require_otp) {
                            window.showToast(response.data.message, 'success');
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        let msg = error.response?.data?.message || 'Login failed';
                        window.showToast(msg, 'error');
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        }
    }
</script>
@endsection
