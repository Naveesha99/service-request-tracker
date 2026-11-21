<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Printer not working',
            'description' => 'Office printer stopped responding.',
            'priority' => 'HIGH',
        ], $overrides);
    }

    public function test_authenticated_user_can_create_request_with_server_owned_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->postJson('/api/requests', $this->validPayload([
            'created_by' => 999,
            'status' => 'RESOLVED',
            'version' => 99,
        ]));

        $response->assertCreated()
            ->assertJsonPath('message', 'Service request created successfully.')
            ->assertJsonPath('data.title', 'Printer not working')
            ->assertJsonPath('data.priority', 'HIGH')
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonPath('data.version', 1)
            ->assertJsonMissing(['password', 'created_by']);

        $request = ServiceRequest::firstOrFail();
        $this->assertSame($user->id, $request->created_by);
        $this->assertSame(ServiceRequestStatus::OPEN, $request->status);
        $this->assertSame(1, $request->version);
    }

    public function test_create_validation_rejects_invalid_fields(): void
    {
        $user = User::factory()->create();
        $cases = [
            ['title' => null],
            ['title' => 'ab'],
            ['title' => str_repeat('a', 101)],
            ['description' => 'short'],
            ['description' => str_repeat('a', 501)],
            ['priority' => 'URGENT'],
        ];

        foreach ($cases as $overrides) {
            $payload = $this->validPayload($overrides);
            $this->actingAs($user, 'web')->postJson('/api/requests', $payload)
                ->assertUnprocessable();
        }
    }

    public function test_missing_description_and_priority_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->postJson('/api/requests', ['title' => 'Printer issue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'priority']);
    }

    public function test_unauthenticated_create_is_rejected(): void
    {
        $this->postJson('/api/requests', $this->validPayload())->assertUnauthorized();
    }

    public function test_listing_returns_only_owned_requests_sorted_newest_first(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $old = ServiceRequest::factory()->create(['created_by' => $user->id, 'created_at' => '2026-01-01 10:00:00']);
        $new = ServiceRequest::factory()->create(['created_by' => $user->id, 'created_at' => '2026-01-02 10:00:00']);
        ServiceRequest::factory()->create(['created_by' => $other->id, 'title' => 'Printer secret']);

        $response = $this->actingAs($user, 'web')->getJson('/api/requests');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_title_search_is_database_scoped_and_partial(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $matching = ServiceRequest::factory()->create(['created_by' => $user->id, 'title' => 'Printer toner replacement']);
        ServiceRequest::factory()->create(['created_by' => $user->id, 'title' => 'Laptop repair']);
        ServiceRequest::factory()->create(['created_by' => $other->id, 'title' => 'Printer unauthorized']);

        $this->actingAs($user, 'web')->getJson('/api/requests?search=printer')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);

        $this->actingAs($user, 'web')->getJson('/api/requests?search=%20%20%20')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_status_priority_and_combined_filters_work(): void
    {
        $user = User::factory()->create();
        $wanted = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'title' => 'Printer network issue',
            'status' => 'OPEN',
            'priority' => 'HIGH',
        ]);
        ServiceRequest::factory()->create(['created_by' => $user->id, 'title' => 'Printer setup', 'status' => 'OPEN', 'priority' => 'LOW']);
        ServiceRequest::factory()->create(['created_by' => $user->id, 'title' => 'Printer outage', 'status' => 'RESOLVED', 'priority' => 'HIGH']);

        $this->actingAs($user, 'web')->getJson('/api/requests?status=OPEN')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($user, 'web')->getJson('/api/requests?priority=HIGH')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($user, 'web')->getJson('/api/requests?search=network&status=OPEN&priority=HIGH')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wanted->id);
    }

    public function test_pagination_defaults_custom_sizes_and_maximum(): void
    {
        $user = User::factory()->create();
        ServiceRequest::factory()->count(12)->create(['created_by' => $user->id]);

        $this->actingAs($user, 'web')->getJson('/api/requests')
            ->assertOk()->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($user, 'web')->getJson('/api/requests?per_page=5&page=2')
            ->assertOk()->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5);

        $this->actingAs($user, 'web')->getJson('/api/requests?per_page=26')
            ->assertUnprocessable();
        $this->actingAs($user, 'web')->getJson('/api/requests?page=0')
            ->assertUnprocessable();
    }

    public function test_invalid_status_priority_and_unauthenticated_listing_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->getJson('/api/requests?status=INVALID')->assertUnprocessable();
        $this->actingAs($user, 'web')->getJson('/api/requests?priority=INVALID')->assertUnprocessable();
        Auth::forgetGuards();
        $this->getJson('/api/requests')->assertUnauthorized();
    }

    public function test_owner_can_retrieve_detail_but_other_users_get_404(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner, 'web')->getJson('/api/requests/' . $request->id)
            ->assertOk()->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.version', 1);

        $this->actingAs($other, 'web')->getJson('/api/requests/' . $request->id)->assertNotFound();
        Auth::forgetGuards();
        $this->getJson('/api/requests/' . $request->id)->assertUnauthorized();
    }

    public function test_unknown_request_detail_returns_404(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->getJson('/api/requests/999999')->assertNotFound();
    }

    public function test_owner_can_retrieve_empty_history(): void
    {
        $owner = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner, 'web')->getJson('/api/requests/' . $request->id . '/history')
            ->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_history_is_oldest_first_and_excludes_sensitive_actor_fields(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $owner->id]);
        $older = RequestStatusHistory::factory()->create([
            'service_request_id' => $request->id,
            'actor_user_id' => $actor->id,
            'created_at' => '2026-01-01 10:00:00',
        ]);
        $newer = RequestStatusHistory::factory()->create([
            'service_request_id' => $request->id,
            'actor_user_id' => $actor->id,
            'created_at' => '2026-01-02 10:00:00',
        ]);

        $response = $this->actingAs($owner, 'web')->getJson('/api/requests/' . $request->id . '/history');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id)
            ->assertJsonPath('data.0.actor.id', $actor->id)
            ->assertJsonMissing(['password']);
    }

    public function test_other_user_unknown_and_unauthenticated_history_are_rejected(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $request = ServiceRequest::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other, 'web')->getJson('/api/requests/' . $request->id . '/history')->assertNotFound();
        $this->actingAs($owner, 'web')->getJson('/api/requests/999999/history')->assertNotFound();
        Auth::forgetGuards();
        $this->getJson('/api/requests/' . $request->id . '/history')->assertUnauthorized();
    }
}
