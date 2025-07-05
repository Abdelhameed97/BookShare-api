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
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\Auth\EmailVerificationController;
use App\Http\Controllers\SocialAuthController;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\BookAiSearchController;

use App\Models\User;


// ============================
// 🔐 Public routes (No auth required)
// ============================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

 //category
// Public category routes (index and show)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Protected category routes (create, update, delete)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});


Route::get('/libraries', function () {
    $owners = User::where('role', 'owner')->get();
    return response()->json(['success' => true, 'data' => $owners]);
});

Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);

// 📧 Email Verification
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum'])
    ->name('verification.send');

Route::post('/resend-verification-email-by-email', [EmailVerificationController::class, 'resendByEmail']);

Route::middleware(['auth:sanctum', 'verified'])->get('/protected', function () {
    return response()->json(['message' => 'You are verified!']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================
// 🔒 Authenticated Routes
// ============================
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users
    Route::apiResource('users', UserController::class);

    // Categories (with full CRUD)
    Route::apiResource('categories', CategoryController::class);

    // Comments
    Route::apiResource('comment', CommentController::class);

    // Contact
    Route::post('/contact', [ContactController::class, 'send']);

    // Ratings
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);

    // Wishlist
    Route::apiResource('wishlist', WishlistController::class);
    Route::post('/wishlist/{id}/move-to-cart', [WishlistController::class, 'moveToCart']);
    Route::post('/wishlist/move-all-to-cart', [WishlistController::class, 'moveAllToCart']);

    // Books (CRUD)
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

    // Stripe Payments
    Route::put('/orders/{order}/payment-method', [PaymentController::class, 'updatePaymentMethod']);
    Route::post('/stripe/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
    Route::post('/stripe/confirm-payment', [StripePaymentController::class, 'confirmPayment']);

    // PayPal Payments
    Route::post('/paypal/create-payment', [PayPalPaymentController::class, 'createPayment']);
    Route::get('/paypal/success/{payment}', [PayPalPaymentController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/cancel/{payment}', [PayPalPaymentController::class, 'cancel'])->name('paypal.cancel');


});

// ============================
// 🔁 Password Reset
// ============================
Route::prefix('auth')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
});

// ============================
// ⭐ Public Ratings
// ============================
Route::get('/ratings', [RatingController::class, 'index']);
Route::get('/ratings/{id}', [RatingController::class, 'show']);

// ============================
// 📧 Test Email Route
// ============================
Route::get('/test-email', function () {
    Mail::raw(
        "BookShare 📚\nEmail sent successfully from BookShare 📚\nTime: " . now() . "\n\nWith best regards, team BookShare",
        function ($message) {
            $message->to('wwwrehabkamal601@gmail.com')
                ->subject('Test Email 🎉 - BookShare');
        }
    );

    return response()->json(['message' => 'Test email sent successfully! Check your inbox.']);
}); // ✅ تم إغلاق القوس هنا بعد ما كان ناقص

// ============================
// 🎟️ Coupons
// ============================
Route::prefix('coupons')->group(function () {
    Route::post('apply', [CouponController::class, 'apply']);
});


// ============================
// 🏦 AI Rag Chat  
// ============================

Route::post('/query', [BookAiSearchController::class, 'search']);