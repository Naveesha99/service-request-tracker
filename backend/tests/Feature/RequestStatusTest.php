<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_to_in_progress_succeeds_and_creates_history(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
            'remark' => 'Technician assigned',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS')
            ->assertJsonPath('data.version', 2);

        $request->refresh();
        $this->assertSame(ServiceRequestStatus::IN_PROGRESS, $request->status);
        $this->assertSame(2, $request->version);

        $history = RequestStatusHistory::sole();
        $this->assertSame($request->id, $history->service_request_id);
        $this->assertSame($user->id, $history->actor_user_id);
        $this->assertSame(ServiceRequestStatus::OPEN, $history->from_status);
        $this->assertSame(ServiceRequestStatus::IN_PROGRESS, $history->to_status);
        $this->assertSame('Technician assigned', $history->remark);
    }

    public function test_in_progress_to_resolved_succeeds_and_creates_second_history(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
        ])->assertOk();

        $response = $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'RESOLVED',
            'expected_version' => 2,
            'remark' => 'Printer repaired',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'RESOLVED')
            ->assertJsonPath('data.version', 3);

        $request->refresh();
        $this->assertSame(ServiceRequestStatus::RESOLVED, $request->status);
        $this->assertSame(3, $request->version);
        $this->assertSame(2, RequestStatusHistory::count());

        $second = RequestStatusHistory::where('to_status', 'RESOLVED')->sole();
        $this->assertSame(ServiceRequestStatus::IN_PROGRESS, $second->from_status);
        $this->assertSame($user->id, $second->actor_user_id);
        $this->assertSame('Printer repaired', $second->remark);
    }

    public function test_invalid_workflow_transitions_do_not_change_request_or_history(): void
    {
        $user = User::factory()->create();

        $cases = [
            ['OPEN', 'RESOLVED', 1],
            ['IN_PROGRESS', 'OPEN', 2],
            ['RESOLVED', 'OPEN', 3],
            ['RESOLVED', 'IN_PROGRESS', 3],
            ['OPEN', 'OPEN', 1],
            ['IN_PROGRESS', 'IN_PROGRESS', 2],
            ['RESOLVED', 'RESOLVED', 3],
        ];

        foreach ($cases as [$initialStatus, $requestedStatus, $initialVersion]) {
            $request = ServiceRequest::factory()->create([
                'created_by' => $user->id,
                'status' => $initialStatus,
                'version' => $initialVersion,
            ]);

            $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
                'status' => $requestedStatus,
                'expected_version' => $initialVersion,
            ])
                ->assertConflict()
                ->assertJsonPath('error.code', 'INVALID_TRANSITION');

            $request->refresh();
            $this->assertSame($initialStatus, $request->status->value);
            $this->assertSame($initialVersion, $request->version);
        }

        $this->assertSame(0, RequestStatusHistory::count());
    }

    public function test_stale_lower_and_higher_versions_are_rejected(): void
    {
        $user = User::factory()->create();

        foreach ([2] as $expectedVersion) {
            $request = ServiceRequest::factory()->create([
                'created_by' => $user->id,
                'status' => 'OPEN',
                'version' => 1,
            ]);

            $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
                'status' => 'IN_PROGRESS',
                'expected_version' => $expectedVersion,
            ])
                ->assertConflict()
                ->assertJsonPath('error.code', 'STALE_VERSION');
        }

        $request = ServiceRequest::factory()->create(['created_by' => $user->id, 'status' => 'OPEN', 'version' => 1]);

        foreach ([2, 99] as $expectedVersion) {
            $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
                'status' => 'IN_PROGRESS',
                'expected_version' => $expectedVersion,
            ])
                ->assertConflict()
                ->assertJsonPath('error.code', 'STALE_VERSION');

            $request->refresh();
            $this->assertSame(ServiceRequestStatus::OPEN, $request->status);
            $this->assertSame(1, $request->version);
        }

        $this->assertSame(0, RequestStatusHistory::count());
    }

    public function test_lower_persisted_version_is_also_rejected_after_update(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
        ])->assertOk();

        $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'RESOLVED',
            'expected_version' => 1,
        ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'STALE_VERSION');

        $request->refresh();
        $this->assertSame(ServiceRequestStatus::IN_PROGRESS, $request->status);
        $this->assertSame(2, $request->version);
        $this->assertSame(1, RequestStatusHistory::count());
    }

    public function test_owner_cross_user_unknown_and_unauthenticated_rules(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
        ])->assertNotFound();

        Auth::forgetGuards();
        $this->patchJson('/api/requests/' . $request->id . '/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
        ])->assertUnauthorized();

        $this->actingAs($owner, 'web')->patchJson('/api/requests/999999/status', [
            'status' => 'IN_PROGRESS',
            'expected_version' => 1,
        ])->assertNotFound();

        $this->assertSame(0, RequestStatusHistory::count());
    }

    public function test_validation_rejects_missing_status_version_and_invalid_values(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        $cases = [
            [],
            ['status' => 'INVALID', 'expected_version' => 1],
            ['status' => 'IN_PROGRESS'],
            ['status' => 'IN_PROGRESS', 'expected_version' => 0],
            ['status' => 'IN_PROGRESS', 'expected_version' => 'one'],
            ['status' => 'IN_PROGRESS', 'expected_version' => 1, 'remark' => str_repeat('a', 501)],
        ];

        foreach ($cases as $payload) {
            $this->actingAs($user, 'web')
                ->patchJson('/api/requests/' . $request->id . '/status', $payload)
                ->assertUnprocessable();
        }

        $this->assertSame(0, RequestStatusHistory::count());
    }

    public function test_history_insertion_failure_rolls_back_request_update(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $user->id]);

        DB::statement(<<<'SQL'
CREATE TRIGGER fail_request_status_history_insert
BEFORE INSERT ON request_status_histories
BEGIN
    SELECT RAISE(ABORT, 'forced history failure');
END
SQL);

        try {
            $this->actingAs($user, 'web')->patchJson('/api/requests/' . $request->id . '/status', [
                'status' => 'IN_PROGRESS',
                'expected_version' => 1,
            ]);
        } catch (\Throwable) {
        }

        $request->refresh();
        $this->assertSame(ServiceRequestStatus::OPEN, $request->status);
        $this->assertSame(1, $request->version);
        $this->assertSame(0, RequestStatusHistory::count());

        DB::statement('DROP TRIGGER IF EXISTS fail_request_status_history_insert');
    }
}
