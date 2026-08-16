<?php

use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('forbids non-administrators from viewing the dashboard', function () {
    [, , $manager, $member] = makeOrgWithUsers();

    $this->actingAs($manager)->getJson('/api/dashboard')->assertForbidden();
    $this->actingAs($member)->getJson('/api/dashboard')->assertForbidden();
});

it('reports request counts by status, scoped to the administrator\'s own organization', function () {
    [$organizationA, $adminA] = makeOrgWithUsers();
    [$organizationB, $adminB] = makeOrgWithUsers();

    Request::factory()->count(2)->create(['organization_id' => $organizationA->id, 'created_by' => $adminA->id, 'status' => RequestStatus::Open]);
    Request::factory()->create(['organization_id' => $organizationA->id, 'created_by' => $adminA->id, 'status' => RequestStatus::Draft]);
    // Noise in another organization — must never affect org A's numbers.
    Request::factory()->count(5)->create(['organization_id' => $organizationB->id, 'created_by' => $adminB->id, 'status' => RequestStatus::Open]);

    $response = $this->actingAs($adminA)->getJson('/api/dashboard');

    $response->assertOk()
        ->assertJsonPath('requests_by_status.open', 2)
        ->assertJsonPath('requests_by_status.draft', 1)
        ->assertJsonPath('requests_by_status.total', 3);
});

it('computes the average resolution time from status history', function () {
    [$organization, $admin] = makeOrgWithUsers();

    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $admin->id,
        'status' => RequestStatus::Resolved,
        'created_at' => now()->subHours(10),
    ]);
    $request->statusHistories()->create([
        'organization_id' => $organization->id,
        'changed_by' => $admin->id,
        'from_status' => RequestStatus::InProgress,
        'to_status' => RequestStatus::Resolved,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->getJson('/api/dashboard');

    // json_encode drops the trailing ".0" for whole-number floats, so the
    // decoded value here is the int 10, not the float 10.0.
    $response->assertOk()->assertJsonPath('avg_resolution_hours', 10);
});

it('returns null average resolution time when nothing has been resolved yet', function () {
    [, $admin] = makeOrgWithUsers();

    $this->actingAs($admin)
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('avg_resolution_hours', null);
});

it('never exposes failed job payloads, only the exception and timing', function () {
    [, $admin] = makeOrgWithUsers();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'rabbitmq',
        'queue' => 'default',
        'payload' => json_encode(['data' => ['command' => 'sensitive-serialized-business-data']]),
        'exception' => "RuntimeException: something broke\n#0 stack trace...",
        'failed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->getJson('/api/dashboard');

    $response->assertOk()->assertJsonPath('recent_errors.0.exception', 'RuntimeException: something broke');
    expect($response->getContent())->not->toContain('sensitive-serialized-business-data');
});
