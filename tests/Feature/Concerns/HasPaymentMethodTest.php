<?php

namespace Elbgoods\Stripe\Tests\Feature\Concerns;

use Astrotomic\PhpunitAssertions\Laravel\ModelAssertions;
use Elbgoods\Stripe\Models\PaymentMethod;
use Elbgoods\Stripe\Tests\Feature\TestCase;
use Elbgoods\Stripe\Tests\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Throwable;

final class HasPaymentMethodTest extends TestCase
{
    public function test_setup_payment_method_intent_without_customer_id_fails(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Stripe\Customer].');
        $user->setupPaymentMethodIntent(['card']);
    }

    public function test_setup_payment_method_intent_with_invalid_customer_id_fails(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->create([
            'stripe_customer_id' => '4Cd70i5yS19tMriM',
        ]);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Stripe\Customer] 4Cd70i5yS19tMriM');
        $user->setupPaymentMethodIntent(['card']);
    }

    public function test_setup_payment_method_intent(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->stripeCustomer()->create();

        $intent = $user->setupPaymentMethodIntent(['card']);
        Assert::assertInstanceOf(SetupIntent::class, $intent);
        Assert::assertSame($user->getStripeCustomerId(), $intent->customer);
        Assert::assertArraySubset(['card'], $intent->payment_method_types);
    }

    public function test_create_payment_method_by_stripe_id(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->stripeCustomer()->create();

        $stripePaymentMethod = $this->stripeCardPaymentMethod($user->findOrFailStripeCustomer());

        $paymentMethod = $user->createPaymentMethodByStripePaymentMethodId($stripePaymentMethod->id);

        Assert::assertInstanceOf(PaymentMethod::class, $paymentMethod);
        Assert::assertSame(1, $user->payment_methods()->count());
        ModelAssertions::assertRelated($user, 'payment_methods', $paymentMethod);

        Assert::assertSame('card', $paymentMethod->stripe_payment_method_type);
        Assert::assertSame($stripePaymentMethod->id, $paymentMethod->stripe_payment_method_id);
        Assert::assertSame('visa', $paymentMethod->card_brand);
        Assert::assertSame('US', $paymentMethod->card_country);
        Assert::assertSame('4242', $paymentMethod->last_four);
        Assert::assertTrue($paymentMethod->expires_at->equalTo(
            now()->addYear()->startOfMonth()->startOfDay()
        ));
        Assert::assertTrue($paymentMethod->is_primary);
    }

    public function test_second_payment_method_is_not_primary_by_default(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->stripeCustomer()->create();

        $stripePaymentMethod = $this->stripeCardPaymentMethod($user->findOrFailStripeCustomer());
        $paymentMethod1 = $user->createPaymentMethodByStripePaymentMethodId($stripePaymentMethod->id);

        $stripePaymentMethod = $this->stripeCardPaymentMethod($user->findOrFailStripeCustomer());
        $paymentMethod2 = $user->createPaymentMethodByStripePaymentMethodId($stripePaymentMethod->id);

        Assert::assertSame(2, $user->payment_methods()->count());

        Assert::assertInstanceOf(PaymentMethod::class, $paymentMethod1);
        ModelAssertions::assertRelated($user, 'payment_methods', $paymentMethod1);
        Assert::assertTrue($paymentMethod1->is_primary);

        Assert::assertInstanceOf(PaymentMethod::class, $paymentMethod2);
        ModelAssertions::assertRelated($user, 'payment_methods', $paymentMethod2);
        Assert::assertFalse($paymentMethod2->is_primary);
    }

    public function test_delete_payment_methods_after_user_is_deleted(): void
    {
        /** @var \Elbgoods\Stripe\Tests\Models\User $user */
        $user = User::factory()->stripeCustomer()->create();

        $stripePaymentMethod1 = $this->stripeCardPaymentMethod($user->findOrFailStripeCustomer());
        $user->createPaymentMethodByStripePaymentMethodId($stripePaymentMethod1->id);

        $stripePaymentMethod2 = $this->stripeCardPaymentMethod($user->findOrFailStripeCustomer());
        $user->createPaymentMethodByStripePaymentMethodId($stripePaymentMethod2->id);

        Assert::assertSame(2, $user->payment_methods()->count());

        $user->delete();

        Assert::assertSame(0, PaymentMethod::count());

        try {
            app(StripeClient::class)->paymentMethods->retrieve(
                $stripePaymentMethod1
            );
            Assert::assertTrue(false, 'PaymentMethod should be not retrievable.');
        } catch(Throwable $ex) {
            Assert::assertInstanceOf(InvalidRequestException::class, $ex);
            Assert::assertStringStartsWith('No such PaymentMethod: ', $ex->getMessage());
        }

        try {
            app(StripeClient::class)->paymentMethods->retrieve(
                $stripePaymentMethod2
            );
            Assert::assertTrue(false, 'PaymentMethod should be not retrievable.');
        } catch(Throwable $ex) {
            Assert::assertInstanceOf(InvalidRequestException::class, $ex);
            Assert::assertStringStartsWith('No such PaymentMethod: ', $ex->getMessage());
        }
    }
}