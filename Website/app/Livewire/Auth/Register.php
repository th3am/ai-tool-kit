<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPlan;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $whatsapp_number = '';
    public $country_code = '';
    public $country_name = '';
    public $password = '';
    public $password_confirmation = '';
    public $showPassword = false;

    // Error bags for custom field mapping if needed, but standard validation works
    
    public function mount()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
    }

    public function register(WhatsAppService $whatsappService)
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_number' => 'required|string|unique:users,whatsapp_number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'whatsapp_number' => $this->whatsapp_number,
                'password' => Hash::make($this->password),
                'is_verified' => false,
                'role' => 'user', 
                'plan_id' => SubscriptionPlan::where('slug', 'free')->first()?->id,
                'country_code' => $this->country_code, 
                'country_name' => $this->country_name
            ]);

            // Generate and Send OTP
            $this->generateAndSendOtp($user->whatsapp_number, 'register', $whatsappService);

            DB::commit();

            return $this->redirect(route('otp.verify', [
                'number' => $user->whatsapp_number,
                'purpose' => 'register'
            ]), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('email', 'Registration failed: ' . $e->getMessage());
        }
    }

    protected function generateAndSendOtp($whatsappNumber, $purpose, $whatsappService)
    {
        $otpCode = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        \App\Models\Otp::create([
            'whatsapp_number' => $whatsappNumber,
            'otp_hash' => Hash::make($otpCode),
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'is_used' => false
        ]);

        $whatsappService->sendOtp($whatsappNumber, $otpCode);
    }
    
    // Check if number exists (Real-time validation hook)
    public function checkNumber(\App\Services\WhatsAppService $service)
    {
         if (empty($this->whatsapp_number)) return;
         
         $exists = $service->checkNumber($this->whatsapp_number);
         if (!$exists) {
             $this->addError('whatsapp_number', 'This WhatsApp number does not exist.');
             return false;
         }
         return true;
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth');
    }
}
