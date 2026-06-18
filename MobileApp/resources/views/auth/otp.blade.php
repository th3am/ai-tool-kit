@extends('layouts.mobile', ['hideNav' => true, 'title' => 'Verify OTP'])

@section('content')
<div class="auth-container"
     x-data="{
        whatsapp_number: '',
        purpose: 'login',
        otp: '',
        loading: false,
        resending: false,
        error: '',
        message: '',

        init() {
            const params = new URLSearchParams(window.location.search);
            this.whatsapp_number = params.get('number') || '';
            this.purpose = params.get('purpose') || 'login';

            if (!this.whatsapp_number) {
                window.location.href = '/login';
            }
        },

        cleanOtp() {
            this.otp = this.otp.replace(/\D/g, '').slice(0, 6);
        },

        async submit() {
            this.error = '';
            this.message = '';
            this.cleanOtp();

            if (this.otp.length !== 6) {
                this.error = 'Please enter the 6-digit OTP.';
                return;
            }

            this.loading = true;
            try {
                const res = await Api.post('/otp/verify', {
                    whatsapp_number: this.whatsapp_number,
                    purpose: this.purpose,
                    otp: this.otp,
                });

                $store.app.setToken(res.token);
                $store.app.setUser(res.user);
                window.location.href = '/dashboard';
            } catch (e) {
                this.error = e.message || 'Invalid or expired OTP.';
                this.loading = false;
            }
        },

        async resend() {
            this.error = '';
            this.message = '';
            this.resending = true;

            try {
                await Api.post('/otp/resend', {
                    whatsapp_number: this.whatsapp_number,
                    purpose: this.purpose,
                });
                this.otp = '';
                this.message = 'A new OTP was sent to your WhatsApp.';
            } catch (e) {
                this.error = e.message || 'Could not resend OTP.';
            } finally {
                this.resending = false;
            }
        }
     }">

    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
             style="background: linear-gradient(135deg, #7c3aed, #a855f7);">
            <span class="text-2xl font-bold text-white">E</span>
        </div>
        <h1 class="text-[28px] font-extrabold text-gradient">EduAI</h1>
        <p class="text-sm text-white/40 mt-1">Verify your WhatsApp number</p>
    </div>

    <div class="w-full bg-dark-100 border border-white/[0.07] rounded-3xl p-7 flex flex-col gap-5">
        <div>
            <h2 class="text-xl font-bold text-white">Enter OTP</h2>
            <p class="text-sm text-white/40 mt-1">
                Sent to <span class="text-white/70" x-text="whatsapp_number"></span>
            </p>
        </div>

        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>
        <div class="success-message" x-show="message" x-text="message" style="display:none;"></div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="otp-code">Verification code</label>
                <input id="otp-code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       class="text-center tracking-[0.35em] text-lg"
                       placeholder="000000"
                       x-model="otp"
                       @input="cleanOtp">
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-1" :disabled="loading">
                <div class="spinner spinner-sm" x-show="loading" style="display:none;"></div>
                <span x-text="loading ? 'Verifying...' : 'Verify and Continue'">Verify and Continue</span>
            </button>
        </form>

        <button type="button" class="btn btn-secondary btn-full" @click="resend" :disabled="resending">
            <div class="spinner spinner-sm" x-show="resending" style="display:none;"></div>
            <span x-text="resending ? 'Sending...' : 'Resend OTP'">Resend OTP</span>
        </button>

        <div class="divider"></div>

        <p class="text-center text-sm text-white/40">
            Wrong number?
            <a href="/login" class="text-brand-400 font-semibold hover:text-brand-300 transition-colors">Go back</a>
        </p>
    </div>
</div>
@endsection
