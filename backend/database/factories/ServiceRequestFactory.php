<?php

namespace Database\Factories;

use App\Enums\ServiceRequestPriority;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'priority' => fake()->randomElement(ServiceRequestPriority::cases())->value,
        ];
    }
}
