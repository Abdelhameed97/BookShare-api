<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PayPalPaymentController;
use App\Http\Controllers\API\StripePaymentController;
use App\Http\Controllers\API\StripeWebhookController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\Auth\EmailVerificationController;
use App\Http\Controllers\SocialAuthController;

use App\Models\User;




// Public routes
// ============================
// 🔐 Auth Routes
// ============================

// Register + Login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/libraries', function () {
    $owners = User::where('role', 'owner')->get();
    return response()->json(['success' => true, 'data' => $owners]);
});

Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);

// ============================
// 📧 Email Verification Routes
// ============================

// ✅ 1. المستخدم بيدوس على اللينك اللي وصله في الإيميل
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed']) // ممكن تضيف 'throttle:6,1' لو حبيت
    ->name('verification.verify');

// ✅ 2. إعادة إرسال رابط التفعيل
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum'])
    ->name('verification.send');
// ✅ 2.5 إعادة إرسال رابط التفعيل باستخدام الإيميل
Route::post('/resend-verification-email-by-email', [EmailVerificationController::class, 'resendByEmail']);


// ✅ 3. Test route لحماية الـ verified فقط
Route::middleware(['auth:sanctum', 'verified'])->get('/protected', function () {
    return response()->json(['message' => 'You are verified!']);
});
// ============================


// ✅ هذا الراوت بيرجع بيانات المستخدم الحالي لو معاه توكن
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // User management
    Route::apiResource('users', UserController::class);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Comments
    Route::apiResource('comment', CommentController::class);

    // Ratings
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);

    // Wishlist
    Route::apiResource('wishlist', WishlistController::class);
    Route::post('/wishlist/{id}/move-to-cart', [WishlistController::class, 'moveToCart']);
    Route::post('/wishlist/move-all-to-cart', [WishlistController::class, 'moveAllToCart']);

    // Books
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::get('/cart/check/{bookId}', [CartController::class, 'checkStatus']);

    // Orders
    Route::get('orders/owner', [OrderController::class, 'ownerOrders']);
    Route::post('orders/{order}/accept', [OrderController::class, 'accept']);
    Route::post('orders/{order}/reject', [OrderController::class, 'reject']);
    Route::apiResource('orders', OrderController::class);

    // Order Items
    Route::apiResource('order-items', OrderItemController::class);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unreadNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'deleteNotification']);
    Route::get('/my-notifications', function (Request $request) {
        return response()->json(['notifications' => $request->user()->notifications]);
    });

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::get('/orders/{order}/payment', [PaymentController::class, 'getOrderPayment']);
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);

    // Stripe
    Route::post('/stripe/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
    Route::post('/stripe/confirm-payment', [StripePaymentController::class, 'confirmPayment']);

    // PayPal
    Route::post('/paypal/create-payment', [PayPalPaymentController::class, 'createPayment']);
    Route::get('/paypal/success/{payment}', [PayPalPaymentController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/cancel/{payment}', [PayPalPaymentController::class, 'cancel'])->name('paypal.cancel');
});

// Password Reset
Route::prefix('auth')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
});

// Ratings (public)
Route::get('/ratings', [RatingController::class, 'index']);
Route::get('/ratings/{id}', [RatingController::class, 'show']);

// Test Email
Route::get('/test-email', function () {
    Mail::raw('BookShare \uD83D\uDCDA\n    email sent successfully from BookShare \uD83D\uDCDA\ntime: ' . now() . '\n\nWith best regards, team BookShare', function ($message) {
        $message->to('wwwrehabkamal601@gmail.com')
            ->subject('test email \uD83C\uDF89    - BookShare');
    });

    return response()->json(['message' => 'Test email sent successfully! Check your inbox.']);
});
