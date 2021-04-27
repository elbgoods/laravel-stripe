<?php

namespace Elbgoods\Stripe\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stripe\StripeClient;

/**
 * @property string $stripe_payment_method_id
 * @property string $stripe_payment_method_type
 * @property string|null $card_brand
 * @property string|null $card_country
 * @property string|null $last_four
 * @property \Carbon\Carbon|null $expires_at
 * @property string|null $bank_name
 * @property string|null $iban_country
 * @property bool $is_primary
 * @property-read \Illuminate\Database\Eloquent\Model|\Elbgoods\Stripe\Contracts\Customerable|\Elbgoods\Stripe\Concerns\HasPaymentMethods $customerable
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_primary',
        'stripe_payment_method_id',
        'stripe_payment_method_type',
        'card_brand',
        'last_four',
        'bank_name',
        'iban_country',
        'card_country',
        'expires_at',
    ];

    protected $attributes = [
        'is_primary' => false,
    ];

    protected $casts = [
        'is_primary' => 'bool',
        'expires_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleted(static function (self $model): void {
            app(StripeClient::class)->paymentMethods->detach($model->stripe_payment_method_id);
        });
    }

    public function customerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function makePrimary(): bool
    {
        try {
            app(StripeClient::class)->customers->update($this->customerable->getStripeCustomerId(), [
                'invoice_settings' => [
                    'default_payment_method' => $this->stripe_payment_method_id,
                ],
            ]);
        } catch (Exception $exception) {
            report($exception);
            $this->delete();

            return false;
        }

        // remove primary on others
        $this->customerable->payment_methods()->whereKeyNot($this->getKey())->update([
            'is_primary' => false,
        ]);

        // mark this one primary
        return $this->forceFill([
            'is_primary' => true,
        ])->save();
    }

    public function updateExpiresAt(int $year, int $month): bool
    {
        return $this->forceFill([
            'expires_at' => Carbon::createFromDate(
                $year,
                $month,
            )->startOfMonth()->startOfDay(),
        ])->save();
    }
}
