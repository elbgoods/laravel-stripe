<?php

namespace Elbgoods\Stripe\Concerns;

use Carbon\Carbon;
use Elbgoods\Stripe\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stripe\SetupIntent;
use Stripe\StripeClient;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\Elbgoods\Stripe\Models\PaymentMethod[] $payment_methods
 * @mixin \Elbgoods\Stripe\Contracts\Customerable
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPaymentMethods
{
    public static function bootHasPaymentMethods(): void
    {
        static::deleted(static function (self $model): void {
            $model->payment_methods->every(fn (PaymentMethod $paymentMethod) => $paymentMethod->delete());
        });
    }

    public function payment_methods(): MorphMany
    {
        return $this->morphMany(PaymentMethod::class, 'customerable');
    }

    public function setupPaymentMethodIntent(array $paymentMethodTypes): SetupIntent
    {
        return app(StripeClient::class)->setupIntents->create([
            'customer' => $this->findOrFailStripeCustomer()->id,
            'payment_method_types' => $paymentMethodTypes,
        ]);
    }

    public function createPaymentMethodByStripePaymentMethodId(string $paymentMethodId): PaymentMethod
    {
        $stripePaymentMethod = app(StripeClient::class)->paymentMethods->retrieve(
            $paymentMethodId
        );

        $attributes = [
            'stripe_payment_method_id' => $stripePaymentMethod->id,
            'stripe_payment_method_type' => $stripePaymentMethod->type,
        ];

        if (! empty($stripePaymentMethod->card)) {
            $attributes['card_brand'] = $stripePaymentMethod->card->brand;
            $attributes['card_country'] = $stripePaymentMethod->card->country;
            $attributes['last_four'] = $stripePaymentMethod->card->last4;
            $attributes['expires_at'] = Carbon::createFromDate(
                $stripePaymentMethod->card->exp_year,
                $stripePaymentMethod->card->exp_month,
            )->startOfMonth()->startOfDay();
        }

        if (! empty($stripePaymentMethod->sepa_debit)) {
            $attributes['bank_name'] = $stripePaymentMethod->sepa_debit->bank_name;
            $attributes['iban_country'] = $stripePaymentMethod->sepa_debit->country;
            $attributes['last_four'] = $stripePaymentMethod->sepa_debit->last4;
        }

        /** @var \Elbgoods\Stripe\Models\PaymentMethod $paymentMethod */
        $paymentMethod = $this->payment_methods()->create($attributes);

        if ($this->payment_methods()->count() === 1) {
            $paymentMethod->makePrimary();
        }

        return $paymentMethod;
    }
}
