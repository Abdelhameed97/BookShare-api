<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialAuthController;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ِِِِِAPI\Auth\EmailVerificationController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\API\UserController;

use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);


Route::get('/reset-password/{token}', function (Request $request, $token) {
    $email = $request->query('email');
    return redirect(env('FRONTEND_URL') . "/reset-password?token=$token&email=$email");
})->name('password.reset');

// Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
//     ->middleware(['signed'])
//     ->name('verification.verify');

// Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
//     $request->fulfill();
//     return redirect(env('FRONTEND_URL') . '/verified-success');
// })->middleware(['signed'])->name('verification.verify');

// Route::post('/email/verification-notification', function (Request $request) {
//     $request->user()->sendEmailVerificationNotification();
//     return response()->json(['message' => 'Verification link sent!']);
// })->middleware(['auth:sanctum'])->name('verification.send');
