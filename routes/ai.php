<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookAiSearchController;

Route::post('/ai-search', [BookAiSearchController::class, 'search']);
