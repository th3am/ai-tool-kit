<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\WhatsAppService;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $showPassword = false;

    public function mount()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
    }

    protected function rules()
    {
        return [
            'email' => 'required',
            'password' => 'required',
        ];
    }

    public function login(WhatsAppService $whatsappService)
    {
        $this->validate();

        // Attempt to find user by Email OR WhatsApp Number
        $user = User::where('email', $this->email)
                    ->orWhere('whatsapp_number', $this->email)
                    ->first();

        if (!$user || !Hash::check($this->password, $user->password)) {
            $this->addError('email', 'Invalid credentials.');
            $this->password = ''; // Clear password
            return;
        }

        // 2FA Logic
        // Bypass for 'admin' or special test accounts
        if ($user->whatsapp_number === 'admin' || $user->role === 'admin') {
            auth()->login($user, true);
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // For regular users, send OTP
        try {
            $this->generateAndSendOtp($user->whatsapp_number, 'login', $whatsappService);
            
            // Redirect to OTP Verify
            return $this->redirect(route('otp.verify', [
                'number' => $user->whatsapp_number,
                'purpose' => 'login'
            ]), navigate: true);

        } catch (\Exception $e) {
            $this->addError('email', 'Failed to send OTP: ' . $e->getMessage());
        }
    }

    protected function generateAndSendOtp($whatsappNumber, $purpose, $whatsappService)
    {
        $otpCode = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Store Hashed OTP
        \App\Models\Otp::create([
            'whatsapp_number' => $whatsappNumber,
            'otp_hash' => Hash::make($otpCode),
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'is_used' => false
        ]);

        $whatsappService->sendOtp($whatsappNumber, $otpCode);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
