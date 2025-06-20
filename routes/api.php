<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\OrderController;

use App\Http\Controllers\BookController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\CartController;

use App\Models\User;
use App\Notifications\TestEmailNotification;

// -------------------- Auth Routes --------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Users
    Route::apiResource('users', UserController::class);

    // Comments (protected)
   // Route::apiResource('comment', CommentController::class);

    // Wishlist (protected)
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::get('/wishlist/{id}', [WishlistController::class, 'show']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::put('/wishlist/{id}', [WishlistController::class, 'update']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
    Route::post('/wishlist/move-all-to-cart', [WishlistController::class, 'moveAllToCart']);

    // Book Routes (protected)
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);

    // Orders
    Route::apiResource('/order', OrderController::class);
    Route::apiResource('/order-items', OrderItemController::class);

    // Notifications
    Route::get('/notifications', function (Request $request) {
        return $request->user()->notifications;
    });
});

// -------------------- Public Routes --------------------

// Categories (public)
Route::apiResource('/category', CategoryController::class);
Route::get('/category', [CategoryController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/comments', [CommentController::class, 'index']);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

// Ratings
Route::get('/ratings', [RatingController::class, 'index']);
Route::get('/ratings/{id}', [RatingController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);
});

// Books (public)
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);

// -------------------- Email Test Routes --------------------
Route::get('/test-email-raw', function () {
    Mail::raw('BookShare 📚
email sent successfully from BookShare 📚
time: ' . now() . '

With best regards, team BookShare', function ($message) {
        $message->to('wwwrehabkamal601@gmail.com')
                ->subject('test email 🎉 - BookShare');
    });

    return response()->json(['message' => 'Test email sent successfully! Check your inbox.']);
});

Route::get('/test-email', function () {
    $user = User::find(1); // Change to actual user ID
    $user->notify(new TestEmailNotification());
    return "Email sent!";
});
