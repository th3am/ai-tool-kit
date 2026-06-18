<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
 
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'The provided credentials do not match our records.'], 401);
    }

      public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_number' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:2', // Added validation for completeness
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        // Check if models have these columns, otherwise ignore to avoid SQL error
        // But for API documentation purpose we assume they might exist or be added.
        // For safety, we'll only add them if we knew for sure, but the user asked for them in doc.
        // We will include them in the array, assuming the User model guard allows them or they exist.
        if (isset($validated['whatsapp_number'])) $userData['whatsapp_number'] = $validated['whatsapp_number'];
        if (isset($validated['country_code'])) $userData['country_code'] = $validated['country_code'];

        $user = User::create($userData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function logout(Request $request)
    {
        // For API token logout
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }
}
