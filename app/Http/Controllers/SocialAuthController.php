<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Client;
use App\Models\Owner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Step 1: Redirect user to the provider (Google, GitHub, etc.)
     */
    public function redirectToProvider(Request $request, $provider)
    {
        // Get role from the request (or use 'client' as default)
        $role = $request->query('role', 'client');

        // Store the role in session temporarily
        session(['social_role' => $role]);

        // Redirect to provider login page
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Step 2: Handle the callback from provider
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            // Get user info from provider
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Retrieve role from session or fallback to 'client'
            $role = session('social_role', 'client');

            // Create or update user based on email
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email_verified_at' => now(),
                    'role' => $role,
                    'password' => bcrypt(Str::random(12)),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token ?? null,
                    'provider_refresh_token' => $socialUser->refreshToken ?? null,
                ]
            );

            // If the user is newly created, create the related model
            if ($user->wasRecentlyCreated) {
                if ($role === 'client') {
                    Client::create(['user_id' => $user->id]);
                } elseif ($role === 'owner') {
                    Owner::create(['user_id' => $user->id]);
                }
            }

            // Log the user in
            Auth::login($user);

            // Create a Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend with token and role
            $redirectUrl = env('FRONTEND_URL') . "/social-callback#token=$token&role=$role";

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Social login failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
