<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (!Schema::hasTable('migrations')) {
            Log::warning('🛠️ Table `migrations` was missing. Running migrate:install automatically.');
            Artisan::call('migrate:install');
        }
    }
}
