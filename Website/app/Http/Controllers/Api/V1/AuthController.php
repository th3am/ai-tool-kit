<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Otp;
use App\Models\SubscriptionPlan;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Get(
 *     path="/sanctum/csrf-cookie",
 *     tags={"1. Auth"},
 *     summary="CSRF Handshake",
 *     description="Initialize the CSRF protection cookie. Call this first before Login.",
 *     @OA\Response(
 *         response=204,
 *         description="Cookie Set",
 *         @OA\Header(header="Set-Cookie", description="XSRF-TOKEN cookie", @OA\Schema(type="string"))
 *     )
 * )
 */
class AuthController extends Controller
{
    public function requestOtp(Request $request, WhatsAppService $whatsappService)
    {
        $purpose = $request->input('purpose', 'login');

        if ($purpose === 'register') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'whatsapp_number' => ['required', 'string', 'max:25'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $whatsappNumber = $this->normalizeWhatsappNumber($validated['whatsapp_number']);

            if (User::where('whatsapp_number', $whatsappNumber)->exists()) {
                throw ValidationException::withMessages([
                    'whatsapp_number' => ['This WhatsApp number is already registered.'],
                ]);
            }

            DB::beginTransaction();

            try {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'whatsapp_number' => $whatsappNumber,
                    'password' => Hash::make($validated['password']),
                    'is_verified' => false,
                    'role' => 'user',
                    'plan_id' => SubscriptionPlan::where('slug', 'free')->first()?->id,
                ]);

                $this->generateAndSendOtp($user->whatsapp_number, 'register', $whatsappService);

                DB::commit();

                return response()->json([
                    'message' => 'OTP sent to your WhatsApp number.',
                    'whatsapp_number' => $user->whatsapp_number,
                    'purpose' => 'register',
                    'expires_in' => 300,
                ], 201);
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:25'],
        ]);

        $whatsappNumber = $this->normalizeWhatsappNumber($validated['whatsapp_number']);
        $user = User::where('whatsapp_number', $whatsappNumber)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['No account exists for this WhatsApp number.'],
            ]);
        }

        $this->generateAndSendOtp($user->whatsapp_number, 'login', $whatsappService);

        return response()->json([
            'message' => 'OTP sent to your WhatsApp number.',
            'whatsapp_number' => $user->whatsapp_number,
            'purpose' => 'login',
            'expires_in' => 300,
        ]);
    }

    public function resendOtp(Request $request, WhatsAppService $whatsappService)
    {
        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:25'],
            'purpose' => ['required', 'in:login,register'],
        ]);

        $whatsappNumber = $this->normalizeWhatsappNumber($validated['whatsapp_number']);
        $user = User::where('whatsapp_number', $whatsappNumber)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['No account exists for this WhatsApp number.'],
            ]);
        }

        $this->generateAndSendOtp($user->whatsapp_number, $validated['purpose'], $whatsappService);

        return response()->json([
            'message' => 'A new OTP was sent to your WhatsApp number.',
            'whatsapp_number' => $user->whatsapp_number,
            'purpose' => $validated['purpose'],
            'expires_in' => 300,
        ]);
    }

    public function verifyOtp(Request $request, WhatsAppService $whatsappService)
    {
        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:25'],
            'purpose' => ['required', 'in:login,register'],
            'otp' => ['required', 'digits:6'],
        ]);

        $whatsappNumber = $this->normalizeWhatsappNumber($validated['whatsapp_number']);

        $validOtp = Otp::where('whatsapp_number', $whatsappNumber)
            ->where('purpose', $validated['purpose'])
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $validOtp || ! Hash::check($validated['otp'], $validOtp->otp_hash)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $user = User::where('whatsapp_number', $whatsappNumber)->firstOrFail();

        $validOtp->update(['is_used' => true]);

        if (! $user->is_verified) {
            $user->update(['is_verified' => true]);

            if ($validated['purpose'] === 'register') {
                try {
                    $whatsappService->sendWelcomeMessage($user->whatsapp_number, $user->name);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $user->tokens()->where('name', 'mobile_auth')->delete();
        $token = $user->createToken('mobile_auth')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully.',
            'user' => $user->fresh(),
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"1. Auth"},
     *     summary="Login (Session)",
     *     description="Authenticate user. Requires XSRF-TOKEN header from the handshake.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // Revoke old tokens to prevent accumulation
            $user->tokens()->where('name', 'mobile_auth')->delete();
            $token = $user->createToken('mobile_auth')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user'    => $user,
                'token'   => $token,
            ]);
        }

        return response()->json(['message' => 'The provided credentials do not match our records.'], 401);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"1. Auth"},
     *     summary="Register",
     *     description="Create a new user account and receive an auth token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Ahmed"),
     *             @OA\Property(property="email", type="string", format="email", example="ahmed@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="password_confirmation", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registered Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('mobile_auth')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     tags={"1. Auth"},
     *     summary="Logout",
     *     description="Invalidate current session",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(Request $request)
    {
        // Revoke the current Sanctum token (Bearer token auth)
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Also invalidate session if present
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    private function normalizeWhatsappNumber(string $number): string
    {
        $normalized = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($normalized) < 8 || strlen($normalized) > 20) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Please enter a valid WhatsApp number with country code.'],
            ]);
        }

        return $normalized;
    }

    private function generateAndSendOtp(string $whatsappNumber, string $purpose, WhatsAppService $whatsappService): void
    {
        $otpCode = (string) random_int(100000, 999999);

        Otp::where('whatsapp_number', $whatsappNumber)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        Otp::create([
            'whatsapp_number' => $whatsappNumber,
            'otp_hash' => Hash::make($otpCode),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
        ]);

        if (! $whatsappService->sendOtp($whatsappNumber, $otpCode)) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Failed to send OTP. Please try again.'],
            ]);
        }
    }
}
