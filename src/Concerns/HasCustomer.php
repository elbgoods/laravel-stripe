<?php

namespace Elbgoods\Stripe\Concerns;

use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\TaxId;

/**
 * @property string|null $stripe_customer_id
 *
 * @mixin \Elbgoods\Stripe\Contracts\Customerable
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasCustomer
{
    protected ?Customer $stripeCustomer = null;

    public function findOrCreateStripeCustomer(): Customer
    {
        $customer = $this->findStripeCustomer();

        if ($customer instanceof Customer) {
            return $customer;
        }

        return $this->createStripeCustomer();
    }

    public function findOrFailStripeCustomer(): Customer
    {
        $customer = $this->findStripeCustomer();

        if (!$customer instanceof Customer) {
            throw (new ModelNotFoundException())->setModel(Customer::class, $this->getStripeCustomerId());
        }

        return $customer;
    }

    public function findStripeCustomer(): ?Customer
    {
        if (!$this->hasStripeCustomerId()) {
            return null;
        }

        if ($this->stripeCustomer !== null) {
            return $this->stripeCustomer;
        }

        try {
            $this->stripeCustomer = app(StripeClient::class)->customers->retrieve(
                $this->getStripeCustomerId()
            );

            return $this->stripeCustomer;
        } catch (Exception $exception) {
            report($exception);
        }

        return null;
    }

    public function createStripeCustomer(): Customer
    {
        if ($this->hasStripeCustomerId()) {
            throw new BadMethodCallException();
        }

        $this->stripeCustomer = app(StripeClient::class)->customers->create([
            'name' => $this->getStripeCustomerName(),
            'email' => $this->getStripeCustomerEmail(),
            'address' => $this->getStripeCustomerAddress(),
            'metadata' => $this->getStripeCustomerMetadata(),
        ]);

        $this->forceFill([
            $this->getStripeCustomerIdName() => $this->stripeCustomer->id,
        ])->save();

        return $this->stripeCustomer;
    }

    public function updateStripeCustomer(): self
    {
        if (!$this->hasStripeCustomerId()) {
            throw new BadMethodCallException();
        }

        app(StripeClient::class)->customers->update($this->getStripeCustomerId(), [
            'name' => $this->getStripeCustomerName(),
            'email' => $this->getStripeCustomerEmail(),
            'address' => $this->getStripeCustomerAddress(),
            'metadata' => $this->getStripeCustomerMetadata(),
        ]);

        return $this;
    }

    public function updateStripeTaxInformation(string $taxId, string $type = TaxId::TYPE_EU_VAT, string $exempt = Customer::TAX_EXEMPT_NONE): self
    {
        if (!$this->hasStripeCustomerId()) {
            throw new BadMethodCallException();
        }

        collect(app(StripeClient::class)->customers->allTaxIds($this->getStripeCustomerId())->data)
            ->each(fn(TaxId $tax) => app(StripeClient::class)->customers->deleteTaxId(
                $this->getStripeCustomerId(),
                $tax->id
            ));

        app(StripeClient::class)->customers->createTaxId($this->getStripeCustomerId(), [
            'type' => $type,
            'value' => $taxId,
        ]);

        app(StripeClient::class)->customers->update($this->getStripeCustomerId(), [
            'tax_exempt' => $exempt,
        ]);

        return $this;
    }

    public function hasStripeCustomerId(): bool
    {
        return $this->getStripeCustomerId() !== null;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->getAttribute($this->getStripeCustomerIdName());
    }

    public function getStripeCustomerIdName(): string
    {
        return 'stripe_customer_id';
    }

    protected function getStripeCustomerMetadata(): array
    {
        return [
            'billable_id' => $this->getKey(),
            'billable_type' => Str::of(get_class($this))->classBasename()->slug('_'),
        ];
    }
}