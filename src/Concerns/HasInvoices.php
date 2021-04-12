<?php

namespace Elbgoods\Stripe\Concerns;

use BadMethodCallException;
use Carbon\Carbon;
use Elbgoods\Stripe\Models\Invoice;
use Elbgoods\Stripe\Models\PaymentMethod;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Stripe\TaxId;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\Elbgoods\Stripe\Models\PaymentMethod[] $invoices
 * @mixin \Elbgoods\Stripe\Contracts\Customerable
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasInvoices
{
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'customerable');
    }
}