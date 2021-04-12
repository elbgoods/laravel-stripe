<?php

namespace Elbgoods\Stripe\Contracts;

use Stripe\Customer;

interface Customerable
{
    public function findOrCreateStripeCustomer(): Customer;

    public function findOrFailStripeCustomer(): Customer;

    public function findStripeCustomer(): ?Customer;

    public function createStripeCustomer(): Customer;

    public function hasStripeCustomerId(): bool;

    public function getStripeCustomerId(): ?string;

    public function getStripeCustomerIdName(): string;

    public function getStripeCustomerName(): string;

    public function getStripeCustomerEmail(): string;

    public function getStripeCustomerAddress(): ?array;
}