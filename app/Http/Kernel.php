<?php
namespace App\Http;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Routing\Middleware\SubstituteBindings;


class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        'api' => [
            // ❌ شيل EnsureFrontendRequestsAreStateful لو مش بتستخدم كوكيز
            // 'EnsureFrontendRequestsAreStateful::class',
            'throttle:api',
            SubstituteBindings::class,
        ],
    ];

}
