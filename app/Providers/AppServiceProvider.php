<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();

        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->isSuperAdmin()) {
                return true;
            }

            if ($user instanceof User && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
