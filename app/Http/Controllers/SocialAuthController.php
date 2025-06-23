<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    // Step 1: Redirect to provider
    public function redirectToProvider(Request $request, $provider)
    {
        $role = $request->query('role', 'client'); // لو مفيش role اعتبره client

        // حفظ الدور مؤقتًا في السيشن (ممكن نستخدم redis أو token بدلًا منه في الإنتاج)
        session(['social_role' => $role]);

        // $url = Socialite::driver($provider)->redirect()->getTargetUrl();
        // dd($url); // اطبعه علشان 

        return Socialite::driver($provider)->redirect();
    }

    // Step 2: Handle callback from provider
    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            $role = session('social_role', 'client'); // استرجاع الدور

            // إنشاء أو تحديث المستخدم بناءً على provider_id و provider
            $user = User::updateOrCreate([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ], [
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
                'role' => $role,
                'password' => bcrypt(Str::random(12)), // باسورد وهمي
                'provider_token' => $socialUser->token ?? null,
                'provider_refresh_token' => $socialUser->refreshToken ?? null,
            ]);

            Auth::login($user);

            // إنشاء توكن باستخدام Sanctum
            $token = $user->createToken("social_login")->plainTextToken;

            // إعادة التوجيه إلى React مع التوكن والرول باستخدام hash
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
