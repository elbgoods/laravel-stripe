<?php

namespace Elbgoods\Stripe\Tests\Feature;

use Carbon\Carbon;
use DateTimeInterface;
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

    protected function stripeCardPaymentMethod(
        ?Customer $customer = null,
        ?DateTimeInterface $expiresAt = null
    ): PaymentMethod {
        $expiresAt = Carbon::instance($expiresAt ?? Carbon::now()->addYear());

        $paymentMethod = app(StripeClient::class)->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => $expiresAt->month,
                'exp_year' => $expiresAt->year,
                'cvc' => '314',
            ],
        ]);

        if ($customer !== null) {
            $paymentMethod = app(StripeClient::class)->paymentMethods->attach($paymentMethod->id, [
                'customer' => $customer->id,
            ]);
        }

        return $paymentMethod;
    }
}
