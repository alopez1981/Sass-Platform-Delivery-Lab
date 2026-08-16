<?php

use App\Enums\RequestStatus;
use App\Models\Notification;
use App\Models\Request;

/**
 * Adversarial tests: an authenticated user of organization A deliberately
 * tries to reach organization B's data by guessing/knowing its ID. Every
 * case here must fail as a 404 (as if the record didn't exist), never a
 * 403 — a 403 would confirm the record exists in another tenant, which is
 * itself an information leak (IDOR enumeration).
 */
it('returns 404, not 403, when viewing another organization\'s request', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    $requestB = Request::factory()->create([
        'organization_id' => $adminB->organization_id,
        'created_by' => $adminB->id,
    ]);

    $this->actingAs($adminA)
        ->getJson("/api/requests/{$requestB->id}")
        ->assertNotFound();
});

it('never lists another organization\'s requests, even paginated across many records', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    Request::factory()->count(3)->create([
        'organization_id' => $adminA->organization_id,
        'created_by' => $adminA->id,
    ]);
    Request::factory()->count(3)->create([
        'organization_id' => $adminB->organization_id,
        'created_by' => $adminB->id,
    ]);

    $response = $this->actingAs($adminA)->getJson('/api/requests');

    $organizationIds = collect($response->json('data'))->pluck('organization_id')->unique();

    expect($organizationIds->all())->toBe([$adminA->organization_id]);
});

it('cannot comment on another organization\'s request', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    $requestB = Request::factory()->create([
        'organization_id' => $adminB->organization_id,
        'created_by' => $adminB->id,
    ]);

    $this->actingAs($adminA)
        ->postJson("/api/requests/{$requestB->id}/comments", ['body' => 'Sneaky comment'])
        ->assertNotFound();

    expect($requestB->comments()->count())->toBe(0);
});

it('cannot change the status of another organization\'s request, even as an administrator', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    $requestB = Request::factory()->create([
        'organization_id' => $adminB->organization_id,
        'created_by' => $adminB->id,
        'status' => RequestStatus::Draft,
    ]);

    // Being an Administrator only grants elevated permissions *within your
    // own organization*. It must not bypass tenant isolation.
    $this->actingAs($adminA)
        ->patchJson("/api/requests/{$requestB->id}/status", ['status' => RequestStatus::Open->value])
        ->assertNotFound();

    expect($requestB->fresh()->status)->toBe(RequestStatus::Draft);
});

it('cannot mark another user\'s notification as read', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    $notificationB = Notification::create([
        'organization_id' => $adminB->organization_id,
        'user_id' => $adminB->id,
        'type' => 'request.created',
        'data' => ['request_id' => 1, 'title' => 'Test'],
    ]);

    // Notification also extends TenantScopedModel, so route-model binding
    // scoped to adminA's organization should never even find it.
    $this->actingAs($adminA)
        ->patchJson("/api/notifications/{$notificationB->id}/read")
        ->assertNotFound();

    expect($notificationB->fresh()->read_at)->toBeNull();
});

it('only lists the authenticated user\'s own notifications', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    Notification::create([
        'organization_id' => $adminB->organization_id,
        'user_id' => $adminB->id,
        'type' => 'request.created',
        'data' => ['request_id' => 1, 'title' => 'Org B notification'],
    ]);

    $response = $this->actingAs($adminA)->getJson('/api/notifications');

    expect($response->json())->toBe([]);
});
