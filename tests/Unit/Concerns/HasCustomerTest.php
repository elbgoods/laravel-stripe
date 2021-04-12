<?php

namespace Elbgoods\Stripe\Tests\Unit\Concerns;

use Elbgoods\Stripe\Tests\Models\User;
use Elbgoods\Stripe\Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;

final class HasCustomerTest extends TestCase
{
    public function test_returns_stripe_customer_id(): void
    {
        $id = 'cus_'.Str::random(8);

        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->make([
            'stripe_customer_id' => $id,
        ]);

        Assert::assertTrue($user->hasStripeCustomerId());
        Assert::assertSame($id, $user->getStripeCustomerId());
    }

    public function test_returns_stripe_customer_email(): void
    {
        $email = 'stripe@elbgoods.de';

        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->make([
            'email' => $email,
        ]);

        Assert::assertSame($email, $user->getStripeCustomerEmail());
    }

    public function test_returns_stripe_customer_name(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->make([
            'first_name' => 'Torsten',
            'last_name' => 'Müller',
        ]);

        Assert::assertSame('Torsten Müller', $user->getStripeCustomerName());
    }

    public function test_returns_stripe_customer_address(): void
    {
        $address = [
            'line1' => 'Alter Wall 69',
            'line2' => null,
            'city' => 'Hamburg',
            'postal_code' => '20457',
            'country' => 'DE',
            'state' => null,
        ];

        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->make([
            'address' => $address,
        ]);

        Assert::assertArraySubset($address, $user->getStripeCustomerAddress());
    }
}
