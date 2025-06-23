<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\API\AuthController; // إذا كنت تستخدم AuthController في مكان آخر

class SocialAuthController extends Controller
{
    // Step 1: Redirect to provider
    public function redirectToProvider(Request $request, $provider)
    {
        session(['role' => $request->role]); // نحفظ الدور مؤقتاً في السيشن
        return Socialite::driver($provider)->redirect();
    }

    // Step 2: Handle provider callback
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $role = session('role', 'client'); // لو مفيش دور اعتبره client

            // نحاول نلاقي يوزر بنفس الإيميل أو الـ provider ID
            $user = User::updateOrCreate([
                'provider_id' => $socialUser->id,
            ], [
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'role' => $role,
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]);

            Auth::login($user); // تسجيل الدخول

            // يمكنك استخدام JWT أو Sanctum هنا – مؤقتاً سنرسل توكن وهمي
            $token = $user->createToken("login")->plainTextToken;

            // نرجّع المستخدم لصفحة React مع التوكن والرول
            return redirect()->away(env('FRONTEND_URL') . "/social-callback#token=$token&role=$role");
        } catch (\Exception $e) {
            return redirect()->away(env('FRONTEND_URL') . '/login')->withErrors(['msg' => 'Social login failed.']);
        }
    }
}
