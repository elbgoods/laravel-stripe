<?php

namespace Elbgoods\Stripe\Tests;

use Elbgoods\Stripe\StripeServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;

    protected function resolveApplication(): Application
    {
        $app = parent::resolveApplication();

        $app->useEnvironmentPath(__DIR__.'/..');

        return $app;
    }

    protected function getPackageProviders($app)
    {
        return [StripeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe', [
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
        ]);
    }
}
