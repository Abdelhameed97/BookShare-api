<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BookAiSearchService;
use App\Services\BookAiEmbeddingService;
use App\Services\FaissIndexService;

class BookAiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BookAiSearchService::class);
        $this->app->singleton(BookAiEmbeddingService::class);
        $this->app->singleton(FaissIndexService::class);
    }

    public function boot()
    {
        // ...existing code...
    }
}
