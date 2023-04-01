<?php

namespace App\Providers;

use App;
use App\Models\Property;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        Property::loadMorphMap();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && App::environment(['local', 'staging'])) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(TinkerServiceProvider::class);
        }

        Route::macro('tag', function (string|array $value) {
            $this->action['meta']['tags'] = is_array($value) ? $value : [$value];

            return $this;
        });

        Route::macro('deprecated', function (bool $value) {
            $this->action['meta']['deprecated'] = $value;

            return $this;
        });
    }
}
