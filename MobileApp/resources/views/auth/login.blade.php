@extends('layouts.mobile', ['hideNav' => true, 'title' => 'Login'])

@section('content')
<div class="auth-container"
     x-data="{
        whatsapp_number: '',
        loading: false,
        error: '',

        async submit() {
            this.error = '';
            if (!this.whatsapp_number) {
                this.error = 'Please enter your WhatsApp number.';
                return;
            }

            this.loading = true;
            try {
                const res = await Api.post('/otp/request', {
                    whatsapp_number: this.whatsapp_number,
                    purpose: 'login',
                });
                const number = encodeURIComponent(res.whatsapp_number || this.whatsapp_number);
                window.location.href = `/otp?number=${number}&purpose=login`;
            } catch (e) {
                this.error = e.message || 'Could not send OTP. Please try again.';
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
        <p class="text-sm text-white/40 mt-1">Your AI-powered learning companion</p>
    </div>

    <div class="w-full bg-dark-100 border border-white/[0.07] rounded-3xl p-7 flex flex-col gap-5">
        <div>
            <h2 class="text-xl font-bold text-white">Welcome back</h2>
            <p class="text-sm text-white/40 mt-1">Sign in with your WhatsApp number</p>
        </div>

        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="login-whatsapp">WhatsApp number</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.1a1 1 0 01.95.68l1 3a1 1 0 01-.26 1.04L7.8 8.7a11 11 0 005.5 5.5l.98-.98a1 1 0 011.04-.26l3 1a1 1 0 01.68.95V17a2 2 0 01-2 2h-1C8.82 19 3 13.18 3 6V5z"/>
                        </svg>
                    </span>
                    <input id="login-whatsapp" type="tel" class="pl-10" placeholder="201234567890"
                           x-model="whatsapp_number" autocomplete="tel" inputmode="tel">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-1" :disabled="loading">
                <div class="spinner spinner-sm" x-show="loading" style="display:none;"></div>
                <span x-text="loading ? 'Sending OTP...' : 'Send OTP'">Send OTP</span>
            </button>
        </form>

        <div class="divider"></div>

        <p class="text-center text-sm text-white/40">
            Don't have an account?
            <a href="/register" class="text-brand-400 font-semibold hover:text-brand-300 transition-colors">Create one</a>
        </p>
    </div>

</div>
@endsection
