<?php

namespace Database\Factories;

use App\Enums\ServiceRequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestStatusHistory>
 */
class RequestStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'actor_user_id' => User::factory(),
            'from_status' => ServiceRequestStatus::OPEN->value,
            'to_status' => ServiceRequestStatus::IN_PROGRESS->value,
            'remark' => fake()->optional()->sentence(),
        ];
    }
}
