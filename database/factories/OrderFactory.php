<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'amount' => 2500,
            'currency' => 'XOF',
            'status' => Order::STATUS_PENDING,
            'gateway' => 'fake',
            'payment_reference' => 'fake_'.Str::lower(Str::random(16)),
        ];
    }

    public function paid(): static
    {
        return $this->state(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    }
}
