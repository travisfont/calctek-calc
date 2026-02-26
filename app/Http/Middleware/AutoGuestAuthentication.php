<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AutoGuestAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the user is already authenticated via Sanctum, proceed normally.
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Generate a unique token
        $uniq = uniqid();

        // Create a new guest user
        $guestUser = User::create([
            'name' => 'Guest User '.$uniq,
            'email' => 'guest_'.$uniq.'@example.com',
            'password' => Hash::make(Str::random(16)),
            'is_guest' => true,
        ]);

        // Generate a Sanctum token for the guest user
        $token = $guestUser->createToken('guest-token')->plainTextToken;

        // Log the user in for the current request context
        Auth::setUser($guestUser);

        // Let the request proceed, but append our new header to the response
        $response = $next($request);

        $response->headers->set('X-Guest-Token', $token);

        return $response;
    }
}
