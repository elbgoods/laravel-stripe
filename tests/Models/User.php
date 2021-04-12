<?php

namespace Elbgoods\Stripe\Tests\Models;

use Elbgoods\Stripe\Concerns\HasCustomer;
use Elbgoods\Stripe\Concerns\HasPaymentMethods;
use Elbgoods\Stripe\Contracts\Customerable;
use Elbgoods\Stripe\Tests\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property array|null $address
 */
class User extends Model implements Customerable
{
    use HasFactory;
    use HasCustomer;
    use HasPaymentMethods;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'address',
    ];

    protected $casts = [
        'address' => 'json',
    ];

    public function getStripeCustomerName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getStripeCustomerEmail(): string
    {
        return $this->email;
    }

    public function getStripeCustomerAddress(): ?array
    {
        return $this->address;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}