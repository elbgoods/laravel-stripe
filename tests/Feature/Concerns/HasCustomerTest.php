<?php

namespace Elbgoods\Stripe\Tests\Feature\Concerns;

use Elbgoods\Stripe\Tests\Feature\TestCase;
use Elbgoods\Stripe\Tests\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Testing\Assert;
use Stripe\Customer;
use Stripe\StripeObject;

final class HasCustomerTest extends TestCase
{
    public function test_it_does_not_find_customer_without_customer_id(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create();
        Assert::assertFalse($user->hasStripeCustomerId());

        $customer = $user->findStripeCustomer();
        Assert::assertNull($customer);
    }

    public function test_it_throws_exception_without_customer_id(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create();
        Assert::assertFalse($user->hasStripeCustomerId());

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Stripe\Customer].');
        $user->findOrFailStripeCustomer();
    }

    public function test_it_creates_new_customer_without_customer_id(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create();
        Assert::assertFalse($user->hasStripeCustomerId());

        $customer = $user->findOrCreateStripeCustomer();
        Assert::assertInstanceOf(Customer::class, $customer);
        Assert::assertTrue($user->hasStripeCustomerId());
        Assert::assertIsString($user->getStripeCustomerId());
        Assert::assertStringStartsWith('cus_', $user->getStripeCustomerId());
        Assert::assertSame($customer->id, $user->fresh()->getStripeCustomerId());
    }

    public function test_it_creates_new_customer(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create();
        Assert::assertFalse($user->hasStripeCustomerId());

        $customer = $user->createStripeCustomer();
        Assert::assertInstanceOf(Customer::class, $customer);
        Assert::assertTrue($user->hasStripeCustomerId());
        Assert::assertIsString($user->getStripeCustomerId());
        Assert::assertStringStartsWith('cus_', $user->getStripeCustomerId());
        Assert::assertSame($customer->id, $user->fresh()->getStripeCustomerId());
        Assert::assertNull($customer->address);
    }

    public function test_it_creates_new_customer_with_address(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->address()->create();
        Assert::assertFalse($user->hasStripeCustomerId());

        $customer = $user->createStripeCustomer();
        Assert::assertInstanceOf(Customer::class, $customer);
        Assert::assertTrue($user->hasStripeCustomerId());
        Assert::assertIsString($user->getStripeCustomerId());
        Assert::assertStringStartsWith('cus_', $user->getStripeCustomerId());
        Assert::assertSame($customer->id, $user->fresh()->getStripeCustomerId());
        Assert::assertInstanceOf(StripeObject::class, $customer->address);
        Assert::assertArraySubset($user->address, $customer->address->toArray());
    }
}
