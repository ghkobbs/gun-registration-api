<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CrimeReport;
use App\Observers\CrimeReportObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CrimeReport::observe(CrimeReportObserver::class);
    }
}
