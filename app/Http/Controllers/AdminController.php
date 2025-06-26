<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Client;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'libraries' => Owner::count(),
            'clients' => Client::count(),
            'books' => Book::count(),
            'categories' => Category::count(),
        ]);
    }
} 