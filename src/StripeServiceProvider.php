<?php

namespace Elbgoods\Stripe;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;
use Stripe\StripeClientInterface;

class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClientInterface::class, static function (Container $app): StripeClient {
            return new StripeClient(
                config('services.stripe.secret_key')
            );
        });
        $this->app->alias(StripeClientInterface::class, StripeClient::class);
    }

    public function boot(): void
    {
        // ToDo: publish migration(s)
    }
}