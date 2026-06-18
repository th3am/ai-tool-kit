<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use App\Services\WhatsAppService;

class VerifyOtp extends Component
{
    public $whatsapp_number;
    public $purpose = 'login';
    public $otp = '';
    
    // For timer UI, handled in Alpine mostly but state tracked here? 
    // Actually, timer is better pure Alpine for perf, but resend logic is livewire.

    public function mount()
    {
        $this->whatsapp_number = request()->query('number');
        $this->purpose = request()->query('purpose', 'login');
        
        if(!$this->whatsapp_number) {
            return $this->redirect(route('login'), navigate: true);
        }
    }

    public function verify(WhatsAppService $whatsappService)
    {
        $validOtp = Otp::where('whatsapp_number', $this->whatsapp_number)
            ->where('purpose', $this->purpose)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$validOtp || !Hash::check($this->otp, $validOtp->otp_hash)) {
            $this->addError('otp', 'Invalid or expired OTP.');
            return;
        }

        // OTP is valid
        $user = User::where('whatsapp_number', $this->whatsapp_number)->firstOrFail();

        // Mark OTP as used
        $validOtp->update(['is_used' => true]);

        // If registration verification
        if ($this->purpose === 'register' && !$user->is_verified) {
            $user->update(['is_verified' => true]);
            
            // Send Welcome Message
            try {
                $whatsappService->sendWelcomeMessage($user->whatsapp_number, $user->name);
            } catch (\Exception $e) {
                // Log but don't fail
            }
        }

        // Login the user
        auth()->login($user);
        session()->regenerate();

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function resend(WhatsAppService $whatsappService)
    {
        $otpCode = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        Otp::create([
            'whatsapp_number' => $this->whatsapp_number,
            'otp_hash' => Hash::make($otpCode),
            'purpose' => $this->purpose,
            'expires_at' => $expiresAt,
            'is_used' => false
        ]);

        $whatsappService->sendOtp($this->whatsapp_number, $otpCode);
        
        $this->dispatch('otp-resent'); // To reset alpine timer
    }

    public function render()
    {
        return view('livewire.auth.verify-otp')->layout('layouts.auth');
    }
}
