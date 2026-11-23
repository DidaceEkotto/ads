<?php

namespace App\Providers;

use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use App\Policies\SocialPolicy;
use App\Services\Billing\PaymentGatewayInterface;
use App\Services\Billing\SasPayGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, SasPayGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::policy(SocialAccount::class, SocialPolicy::class);
        Gate::policy(SocialPlacement::class, SocialPolicy::class);
    }
}
