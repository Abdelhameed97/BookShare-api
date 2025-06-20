<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
// use App\Http\Controllers\API\BookController;

use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;

use App\Http\Controllers\BookController;
use App\Http\Controllers\OrderItemController;


use App\Notifications\TestEmailNotification;

use App\Models\User;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
});


//category
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});

// comment
Route::apiResource('/comment', commentController::class)->middleware('auth:sanctum');


// Ratings
Route::get('/ratings', [RatingController::class, 'index']);
Route::get('/ratings/{id}', [RatingController::class, 'show']);

// Protected rating routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);
});

// Wishlist
// Protected wishlist routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::get('/wishlist/{id}', [WishlistController::class, 'show']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::put('/wishlist/{id}', [WishlistController::class, 'update']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
    Route::post('/wishlist/move-all-to-cart', [WishlistController::class, 'moveAllToCart']);
});

// Book routes
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);

// Protected book routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);
});


// Test route for email
Route::get('/test-email', function () {
    Mail::raw('BookShare 📚
    email sent successfully from BookShare 📚
time: ' . now() . '

With best regards, team BookShare', function ($message) {
        $message->to('wwwrehabkamal601@gmail.com')
            ->subject('test email 🎉    - BookShare');
    });

    return response()->json(['message' => 'Test email sent successfully! Check your inbox.']);
});

Route::get('/test-email', function () {
    $user = User::find(1); // Replace with the user's ID you want to send the email to
    $user->notify(new TestEmailNotification());
    return "Email sent!";
});

Route::middleware('auth:sanctum')->get('/notifications', function (Request $request) {
    return $request->user()->notifications;
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
});

// Order
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('order', OrderController::class);
});

// Order Items
Route::middleware('auth:sanctum')->group(
    function () {
        Route::apiResource('/order-items', OrderItemController::class);
    }
);
