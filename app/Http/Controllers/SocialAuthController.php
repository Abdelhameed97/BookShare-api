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
     * Step 1: Redirect user to the provider (Google, LinkedIn, etc.)
     */
    public function redirectToProvider(Request $request, $provider)
    {
        // ✅ فقط خزّن الـ role في السيشن لو جاية من الريكوست (يعني تسجيل جديد فقط)
        if ($request->has('role')) {
            session(['social_role' => $request->query('role')]);
        }

        // ✅ روح يسجّل الدخول من مزوّد OAuth
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Step 2: Handle the callback from provider
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            // ✅ استرجع بيانات المستخدم من provider
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // ✅ دور على يوزر بنفس الإيميل
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // 🔥 لو مفيش role في السيشن معناها إنه جاي من login → ارجعه لاختيار الرول
                if (!session()->has('social_role')) {
                    $redirectGetStarted = env('FRONTEND_URL') . '/get-started';
                    return redirect()->away($redirectGetStarted);
                }

                // ✅ المستخدم جديد وجاي من get-started → أنشئ حسابه
                $role = session('social_role', 'client');

                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $socialUser->getEmail(),
                    'email_verified_at' => now(),
                    'role' => $role,
                    'password' => bcrypt(Str::random(12)),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token ?? null,
                    'provider_refresh_token' => $socialUser->refreshToken ?? null,
                ]);

                if ($role === 'client') {
                    Client::create(['user_id' => $user->id]);
                } elseif ($role === 'owner') {
                    Owner::create(['user_id' => $user->id]);
                }

                // ✅ امسح الدور بتاع اليوزر من السيشن بعد ما استخدمناه
                session()->forget('social_role');

            } else {
                // ✅ موجود بالفعل → استخرج الرول من الداتا بيز
                $role = $user->role;
            }

            // ✅ سجل دخول المستخدم
            Auth::login($user);

            // ✅ أنشئ توكن
            $token = $user->createToken('auth_token')->plainTextToken;

            // ✅ ابني رابط الـ redirect للـ Frontend
            $redirectUrl = env('FRONTEND_URL') . "/social-callback#token=$token&role={$role}";

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Social login failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
