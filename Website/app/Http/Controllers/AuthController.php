<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // -- Views --
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function showOtpForm()
    {
        return view('auth.otp');
    }

    // -- API / Actions (Legacy Ajax) --

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Attempt login with email (or whatever field 'email' input holds, could be phone)
        // For now standard Auth::attempt behaves as 'email'
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            $redirectUrl = $user->is_verified ? route('dashboard') : route('otp.verify', ['number' => $user->whatsapp_number, 'purpose' => 'login']);

            return response()->json([
                'message' => 'Login successful',
                'redirect_url' => $redirectUrl, 
                'user' => $user
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 401);
    }


    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_number' => 'required|string|max:20|unique:users',
            'country_code' => 'nullable|string|max:5',
            'country_name' => 'nullable|string|max:100', // Added validation for country_name
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_number' => $validated['whatsapp_number'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'country_code' => $validated['country_code'] ?? null,
            'country_name' => $validated['country_name'] ?? null,
            'is_verified' => false,
        ]);

        // Generate OTP
        $otpCode = rand(100000, 999999);
        
        \Illuminate\Support\Facades\DB::table('otps')->insert([
            'whatsapp_number' => $user->whatsapp_number,
            'otp_hash' => \Illuminate\Support\Facades\Hash::make($otpCode),
            'purpose' => 'register',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP
        $whatsAppService = new \App\Services\WhatsAppService();
        $whatsAppService->sendOtp($user->whatsapp_number, $otpCode);

        // Security: Ensure user is NOT logged in to force OTP check
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Registration successful. Please check your WhatsApp for the OTP.',
            'redirect_url' => route('otp.verify', ['whatsapp_number' => $user->whatsapp_number]), 
            'whatsapp_number' => $user->whatsapp_number,
        ], 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function verifyOtp(Request $request) 
    { 
        $request->validate([
            'whatsapp_number' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        // Find valid OTP
        $otpRecord = \Illuminate\Support\Facades\DB::table('otps')
            ->where('whatsapp_number', $request->whatsapp_number)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        if (!$otpRecord || !\Illuminate\Support\Facades\Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        // Mark OTP as used
        \Illuminate\Support\Facades\DB::table('otps')
            ->where('id', $otpRecord->id)
            ->update(['is_used' => true, 'updated_at' => now()]);

        // Find user and login
        $user = User::where('whatsapp_number', $request->whatsapp_number)->first();

        if ($user) {
            $user->is_verified = true;
            $user->save();
            Auth::login($user);
            $request->session()->regenerate();
            
            return response()->json([
                'message' => 'Verification successful.',
                'redirect_url' => route('dashboard'),
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'User not found.'], 404);
    }

    public function resendOtp(Request $request) 
    { 
        $request->validate([
            'whatsapp_number' => 'required|string',
        ]);

        $user = User::where('whatsapp_number', $request->whatsapp_number)->first();
        if (!$user) {
             return response()->json(['message' => 'User not found.'], 404);
        }

        // Generate OTP
        $otpCode = rand(100000, 999999);
        
        \Illuminate\Support\Facades\DB::table('otps')->insert([
            'whatsapp_number' => $user->whatsapp_number,
            'otp_hash' => \Illuminate\Support\Facades\Hash::make($otpCode),
            'purpose' => 'resend',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send OTP
        $whatsAppService = new \App\Services\WhatsAppService();
        $whatsAppService->sendOtp($user->whatsapp_number, $otpCode);

        return response()->json(['message' => 'OTP resent successfully.']); 
    }

    public function checkWhatsApp(Request $request) { 
        $request->validate(['whatsapp_number' => 'required']);
        // Reuse service logic if needed, or primarily used by frontend for UX
        $service = new \App\Services\WhatsAppService();
        $exists = $service->checkNumber($request->whatsapp_number);
        return response()->json(['exists' => $exists]); 
    }
}
