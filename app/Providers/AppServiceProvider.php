<?php

namespace App\Providers;

use App\Models\UserNotification;
use App\Observers\UserNotificationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        UserNotification::observe(UserNotificationObserver::class);
    }
}
