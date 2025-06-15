<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\BookController;

use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\WishlistController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\CartController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('users', UserController::class);
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('user', UserController::class);
});

//category
Route::apiResource('/category', \App\Http\Controllers\API\CategoryController::class);
// comment
Route::apiResource('/comment', \App\Http\Controllers\API\CommentController::class)->middleware('auth:sanctum');


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
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::get('/wishlist/{id}', [WishlistController::class, 'show']);
// Protected wishlist routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::put('/wishlist/{id}', [WishlistController::class, 'update']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
});

// Book routes
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);

// Protected book routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);

});

//category
Route::apiResource('/category', CategoryController::class);

// comment
Route::apiResource('/comment', CommentController::class);

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
});
