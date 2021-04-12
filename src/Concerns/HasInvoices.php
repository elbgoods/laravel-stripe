<?php

namespace Elbgoods\Stripe\Concerns;

use Elbgoods\Stripe\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
