<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhatsAppVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->is_verified) {
            return redirect()->route('otp.verify', [
                'number' => auth()->user()->whatsapp_number,
                'purpose' => 'verify'
            ])->with('error', 'Please verify your WhatsApp number to continue.');
        }

        return $next($request);
    }
}
