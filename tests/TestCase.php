<?php

namespace Elbgoods\Stripe\Tests;

use Elbgoods\Stripe\StripeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [StripeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe', [
            'public_key' => 'pk_test_51IdAmdHnEAwyuIwaAzIYaqz9Ycz4zLYmdjTGaKB7tgFZNkuQGc9LaWcZQKzFQu66hFF1M4qbcy9PUHigxYYJuzYi00Mzy9k8Hx',
            'secret_key' => 'sk_test_51IdAmdHnEAwyuIwabSnpQsEwO6ioRo3nISGFQYJILdtqu9Jq1sLZ361luAmADJMgQShEt5wzw2tRyI7IS6cozyFv00DeEqYs6P',
        ]);
    }
}
