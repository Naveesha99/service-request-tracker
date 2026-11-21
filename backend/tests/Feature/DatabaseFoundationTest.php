<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_request_is_associated_with_its_creator(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        $this->assertSame($user->id, $request->creator->id);
        $this->assertTrue($user->serviceRequests()->whereKey($request->id)->exists());
    }

    public function test_user_can_retrieve_associated_service_requests(): void
    {
        $user = User::factory()->create();
        ServiceRequest::factory()->count(3)->create(['created_by' => $user->id]);

        $this->assertCount(3, $user->serviceRequests()->get());
    }

    public function test_service_request_can_have_multiple_status_histories(): void
    {
        $request = ServiceRequest::factory()->create();

        RequestStatusHistory::factory()->count(2)->create([
            'service_request_id' => $request->id,
            'actor_user_id' => $request->creator->id,
        ]);

        $this->assertCount(2, $request->statusHistories()->get());
    }

    public function test_history_references_service_request_and_actor(): void
    {
        $request = ServiceRequest::factory()->create();
        $history = RequestStatusHistory::factory()->create([
            'service_request_id' => $request->id,
            'actor_user_id' => $request->creator->id,
        ]);

        $this->assertSame($request->id, $history->serviceRequest->id);
        $this->assertSame($request->creator->id, $history->actor->id);
    }

    public function test_demo_user_seeding_is_exact_and_idempotent(): void
    {
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->assertCount(2, User::whereIn('email', [
            'user1@example.com',
            'user2@example.com',
        ])->get());

        $this->assertCount(2, User::all());

        $userOne = User::where('email', 'user1@example.com')->firstOrFail();
        $userTwo = User::where('email', 'user2@example.com')->firstOrFail();

        $this->assertSame('Demo User One', $userOne->name);
        $this->assertSame('Demo User Two', $userTwo->name);
        $this->assertTrue(Hash::check('Password123!', $userOne->password));
        $this->assertTrue(Hash::check('Password123!', $userTwo->password));
    }

    public function test_service_request_defaults_status_and_version(): void
    {
        $request = ServiceRequest::factory()->create()->refresh();

        $this->assertSame(ServiceRequestStatus::OPEN, $request->status);
        $this->assertSame(1, $request->version);
    }
}
