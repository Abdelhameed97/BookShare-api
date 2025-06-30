<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;


use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\NewAccessToken;


use App\Models\User;
use App\Models\Category;
use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Policies\UserPolicy;
use App\Policies\CategoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
        Order::class => OrderPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('create-payment', function ($user, $order) {
            return $order->client_id === $user->id || $user->is_admin;
        });


        // ✅ تخصيص رابط تحقق الإيميل

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            // ⏳ رابط التحقق الأساسي من Laravel
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // 🔑 إصدار توكن باستخدام Sanctum
            $token = $notifiable->createToken('email-verification')->plainTextToken;

            // 🧭 دمج رابط التحقق مع التوكن للفرونت إند
            return $frontendUrl . '/verify-email?' . parse_url($verifyUrl, PHP_URL_QUERY) . '&token=' . $token;
        });

    }

    
}
