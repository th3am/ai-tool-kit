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

<div class="flex-1 w-full max-w-md px-4 sm:px-6 md:px-0" x-data="registerForm()">
    <div class="relative backdrop-blur-3xl bg-white/50 dark:bg-white/10 rounded-2xl p-6 sm:p-8 md:p-10 border border-gray-200 dark:border-white/5 transition-all duration-700 ease-in-out">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-white/20 to-white/40 dark:via-[#060b21]/10 dark:to-[#060b21]/40 rounded-2xl pointer-events-none transition-all duration-700"></div>
        <h2 class="text-3xl font-semibold mb-2 relative z-10">Create Account</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6 text-md relative z-10">Get started with your free account</p>

        <form @submit.prevent="submit" class="space-y-5 relative z-10">
            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-regular fa-user text-blue-500 dark:text-blue-400"></i> Full name
                </label>
                <input type="text" x-model="form.name" required placeholder="John Doe" class="w-full h-[50px] bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 py-2 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition"/>
            </div>

            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-regular fa-envelope text-blue-500 dark:text-blue-400"></i> Email Address
                </label>
                <input type="email" x-model="form.email" required placeholder="you@example.com" class="w-full h-[50px] bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 py-2 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition"/>
            </div>

            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-brands fa-whatsapp text-green-500 dark:text-green-400"></i> WhatsApp
                </label>
                <input type="tel" id="whatsapp_input" class="w-full h-[50px] bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 py-2 rounded-lg outline-none placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition"/>
            </div>

            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-solid fa-lock text-blue-500 dark:text-blue-400"></i> Password
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" x-model="form.password" required placeholder="••••••••••" class="w-full h-[50px] bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 pr-10 py-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition"/>
                    <i @click="showPassword = !showPassword" class="fa-regular absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-lg mb-1">
                    <i class="fa-solid fa-lock text-blue-500 dark:text-blue-400"></i> Confirm Password
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" x-model="form.password_confirmation" required placeholder="••••••••••" class="w-full h-[50px] bg-[#DBDDE9] dark:bg-white/10 text-gray-800 dark:text-white px-4 pr-10 py-2 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 transition"/>
                </div>
            </div>

            <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] text-white py-2.5 rounded-lg font-semibold transition-all duration-200 shadow-lg disabled:opacity-50">
                <span x-show="!loading">Create Account</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin"></i> Processing...</span>
            </button>

            <p class="text-center text-gray-600 dark:text-gray-400 mt-4 text-sm">
                Already have an account?
                <a href="{{ route('login') }}" class="text-blue-500 dark:text-blue-400 hover:underline">Sign In</a>
            </p>
        </form>
    </div>
</div>

<style>
    .iti { width: 100%; display: block; }
    .iti__country-list { background-color: white; color: #333; border: 1px solid #e5e7eb; }
    .dark .iti__country-list { background-color: #1f2937; color: #f3f4f6; border-color: #374151; }
    .dark .iti__country-list li:hover, .dark .iti__country-list li.iti__highlight { background-color: #374151; }
    .dark .iti__flag-container .iti__selected-flag { background-color: transparent; }
    .dark .iti__flag-container .iti__selected-flag:hover { background-color: rgba(255,255,255,0.05); }
</style>

<script>
    function registerForm() {
        return {
            form: {
                name: '',
                email: '',
                whatsapp_number: '',
                country_code: 'us',
                country_name: 'United States',
                password: '',
                password_confirmation: ''
            },
            loading: false,
            showPassword: false,
            iti: null,
            init() {
                const input = document.querySelector("#whatsapp_input");
                this.iti = window.intlTelInput(input, {
                    initialCountry: "auto",
                    geoIpLookup: callback => {
                        fetch("https://ipapi.co/json")
                        .then(res => res.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback("us"));
                    },
                    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                    separateDialCode: true,
                });

                input.addEventListener("countrychange", () => {
                    this.form.country_code = this.iti.getSelectedCountryData().iso2;
                    this.form.country_name = this.iti.getSelectedCountryData().name;
                });
            },
            submit() {
                this.form.whatsapp_number = this.iti.getNumber().replace('+', '');
                this.loading = true;

                axios.post('{{ route('register') }}', this.form)
                    .then(response => {
                        window.showToast(response.data.message, 'success');
                        setTimeout(() => {
                           window.location.href = "{{ route('otp.verify') }}?number=" + encodeURIComponent(response.data.whatsapp_number) + "&purpose=register";
                        }, 2000);
                    })
                    .catch(error => {
                        let msg = error.response?.data?.message || 'Registration failed';
                        if (error.response?.data?.errors) {
                            msg = Object.values(error.response.data.errors).flat().join(', ');
                        }
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
