@extends('layouts.mobile', ['hideNav' => true, 'title' => 'Register'])

@section('content')
<div class="auth-container"
     x-data="{
        name: '',
        email: '',
        whatsapp_number: '',
        password: '',
        password_confirmation: '',
        loading: false,
        error: '',

        async submit() {
            this.error = '';
            if (!this.name || !this.email || !this.whatsapp_number || !this.password) {
                this.error = 'Please fill in all required fields.';
                return;
            }
            if (this.password !== this.password_confirmation) {
                this.error = 'Passwords do not match.';
                return;
            }
            if (this.password.length < 8) {
                this.error = 'Password must be at least 8 characters.';
                return;
            }

            this.loading = true;
            try {
                const res = await Api.post('/otp/request', {
                    purpose: 'register',
                    name: this.name,
                    email: this.email,
                    whatsapp_number: this.whatsapp_number,
                    password: this.password,
                    password_confirmation: this.password_confirmation,
                });
                const number = encodeURIComponent(res.whatsapp_number || this.whatsapp_number);
                window.location.href = `/otp?number=${number}&purpose=register`;
            } catch (e) {
                this.error = e.message || 'Registration failed.';
                this.loading = false;
            }
        }
     }">

    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
             style="background: linear-gradient(135deg, #7c3aed, #a855f7);">
            <span class="text-2xl font-bold text-white">E</span>
        </div>
        <h1 class="text-[28px] font-extrabold text-gradient">EduAI</h1>
        <p class="text-sm text-white/40 mt-1">Start learning smarter with AI</p>
    </div>

    <div class="w-full bg-dark-100 border border-white/[0.07] rounded-3xl p-7 flex flex-col gap-5">
        <div>
            <h2 class="text-xl font-bold text-white">Create account</h2>
            <p class="text-sm text-white/40 mt-1">We will verify your WhatsApp number</p>
        </div>

        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="reg-name">Full name</label>
                <input id="reg-name" type="text" placeholder="Ahmed Mohamed"
                       x-model="name" autocomplete="name">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="reg-email">Email address</label>
                <input id="reg-email" type="email" placeholder="you@example.com"
                       x-model="email" autocomplete="email" inputmode="email">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="reg-whatsapp">WhatsApp number</label>
                <input id="reg-whatsapp" type="tel" placeholder="201234567890"
                       x-model="whatsapp_number" autocomplete="tel" inputmode="tel">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="reg-password">Password</label>
                <input id="reg-password" type="password" placeholder="At least 8 characters"
                       x-model="password" autocomplete="new-password">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="reg-confirm">Confirm password</label>
                <input id="reg-confirm" type="password" placeholder="Repeat password"
                       x-model="password_confirmation" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-1" :disabled="loading">
                <div class="spinner spinner-sm" x-show="loading" style="display:none;"></div>
                <span x-text="loading ? 'Sending OTP...' : 'Send OTP'">Send OTP</span>
            </button>
        </form>

        <div class="divider"></div>

        <p class="text-center text-sm text-white/40">
            Already have an account?
            <a href="/login" class="text-brand-400 font-semibold hover:text-brand-300 transition-colors">Sign in</a>
        </p>
    </div>

</div>
@endsection
