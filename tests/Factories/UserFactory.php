<?php

namespace Elbgoods\Stripe\Tests\Factories;

use Elbgoods\Stripe\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
        ];
    }

    public function address(): self
    {
        return $this->state([
            'address' => [
                'line1' => $this->faker->streetAddress,
                'line2' => null,
                'city' => $this->faker->city,
                'postal_code' => $this->faker->postcode,
                'country' => $this->faker->countryCode,
                'state' => $this->faker->state,
            ],
        ]);
    }

    public function stripeCustomer(): self
    {
        return $this->afterCreating(fn (User $user) => $user->createStripeCustomer());
    }
}
