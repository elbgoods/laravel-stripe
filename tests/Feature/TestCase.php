<?php

namespace Elbgoods\Stripe\Tests\Feature;

use Elbgoods\Stripe\Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom([
            '--path' => [
                __DIR__.'/../migrations',
                __DIR__.'/../../migrations',
            ],
        ]);
    }

    protected function stripeCardPaymentMethod(Customer $customer): PaymentMethod
    {
        $paymentMethod = app(StripeClient::class)->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => date('m'),
                'exp_year' => date('Y') + 1,
                'cvc' => '314',
            ],
        ]);

        return app(StripeClient::class)->paymentMethods->attach($paymentMethod->id, [
            'customer' => $customer->id,
        ]);
    }
}
