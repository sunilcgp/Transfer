<?php

namespace Database\Factories;

use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(32),
            'expires_at' => now()->addWeek(),
            'sent_at' => now(),
            'file_count' => fake()->numberBetween(1, 5),
            'total_size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'sender_email' => fake()->safeEmail(),
            'recipient_emails' => [fake()->safeEmail()],
            'message' => fake()->sentence(),
        ];
    }
}
