<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;


Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);


Route::get('/reset-password/{token}', function ($token) {
    return redirect(env('FRONTEND_URL') . "/reset-password?token=$token");
})->name('password.reset');

