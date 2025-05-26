<?php

use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ratings
Route::get('/ratings', [RatingController::class, 'index']);
Route::get('/ratings/{id}', [RatingController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);
});

// Wishlist
Route::get('/Wishlist', [WishlistController::class, 'index']);
Route::get('/Wishlist/{id}', [WishlistController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/Wishlist', [WishlistController::class, 'store']);
    Route::put('/Wishlist/{id}', [WishlistController::class, 'update']);
    Route::delete('/Wishlist/{id}', [WishlistController::class, 'destroy']);
});
