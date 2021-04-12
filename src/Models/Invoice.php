<?php

namespace Elbgoods\Stripe\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceItem as StripeInvoiceItem;
use Stripe\StripeClient;

/**
 * @property string|null $stripe_invoice_id
 * @property string|null $stripe_invoice_status
 * @property string|null $stripe_payment_intent_status
 * @property string|null $stripe_pdf_url
 * @property string|null $stripe_invoice_number
 * @property int|null $total
 * @property string|null $currency
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $billing_range_start
 * @property \Carbon\Carbon|null $billing_range_end
 * @property-read \Illuminate\Database\Eloquent\Model|\Elbgoods\Stripe\Contracts\Customerable|\Elbgoods\Stripe\Concerns\HasInvoices $customerable
 */
class Invoice extends Model
{
    use HasFactory;

    protected $casts = [
        'paid_at' => 'datetime',
        'billing_range_start' => 'datetime',
        'billing_range_end' => 'datetime',
    ];

    public function customerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getStripeInvoice(): ?StripeInvoice
    {
        return app(StripeClient::class)->invoices->retrieve($this->stripe_invoice_id);
    }

    public function sendInvoiceToCustomer(): void
    {
        // ToDo: think about it - stripe can send invoices itself
    }

    public function createAtStripe(): bool
    {
        $stripeInvoice = app(StripeClient::class)->invoices->create([
            'customer' => $this->customerable->getStripeCustomerId(),
            'collection_method' => 'charge_automatically',
            'custom_fields' => $this->getStripeInvoiceCustomFields(),
            'auto_advance' => false, // dont finalize right now
            'default_tax_rates' => $this->getStripeDefaultTaxRates(),
        ]);

        $this->stripe_invoice_id = $stripeInvoice->id;
        $this->stripe_invoice_status = $stripeInvoice->status;
        $this->stripe_payment_intent_status = optional($stripeInvoice->payment_intent)->status;

        return $this->save();
    }

    public function finalize(): bool
    {
        $stripeInvoice = app(StripeClient::class)->invoices->finalizeInvoice($this->stripe_invoice_id, [
            'auto_advance' => true, // attempt to pay
        ]);

        $this->stripe_invoice_status = $stripeInvoice->status;
        $this->stripe_payment_intent_status = optional($stripeInvoice->payment_intent)->status;
        $this->stripe_pdf_url = $stripeInvoice->invoice_pdf;
        $this->stripe_invoice_number = $stripeInvoice->number;
        $this->total = $stripeInvoice->total;
        $this->currency = $stripeInvoice->currency;

        return $this->save();
    }

    public function updateFromStripe(): bool
    {
        $stripeInvoice = $this->getStripeInvoice();

        if ($stripeInvoice === null) {
            return false; // ToDo: Exception?
        }

        $this->stripe_invoice_status = $stripeInvoice->status;
        $this->stripe_payment_intent_status = optional($stripeInvoice->payment_intent)->status;
        $this->stripe_pdf_url = $stripeInvoice->invoice_pdf;
        $this->stripe_invoice_number = $stripeInvoice->number;
        $this->total = $stripeInvoice->total;
        $this->currency = $stripeInvoice->currency;

        return $this->save();
    }

    public function markAsPaid(): bool
    {
        $this->paid_at = now();
        $this->stripe_invoice_status = StripeInvoice::STATUS_PAID;

        return $this->save();
    }

    public function createStripeInvoiceItem(string $description, int $amount, string $currency = 'eur'): StripeInvoiceItem
    {
        return app(StripeClient::class)->invoiceItems->create([
            'customer' => $this->customerable->getStripeCustomerId(),
            'description' => $description,
            'amount' => $amount,
            'currency' => Str::lower($currency),
        ]);
    }

    protected function getStripeInvoiceCustomFields(): array
    {
        return [];
    }

    protected function getStripeDefaultTaxRates(): array
    {
        return [];
    }
}
