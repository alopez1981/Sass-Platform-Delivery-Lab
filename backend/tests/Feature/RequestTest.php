<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Request;
use App\Models\User;

it('creates a request as draft and notifies managers asynchronously', function () {
    [$organization, $admin, $manager, $member] = makeOrgWithUsers();

    $response = $this->actingAs($member)->postJson('/api/requests', [
        'title' => 'Broken chair in room 3',
        'description' => 'Needs replacement',
    ]);

    $response->assertCreated()
        ->assertJsonPath('title', 'Broken chair in room 3')
        ->assertJsonPath('status', RequestStatus::Draft->value);

    $request = Request::firstWhere('title', 'Broken chair in room 3');
    expect($request->organization_id)->toBe($organization->id);
    expect($request->created_by)->toBe($member->id);

    // No assignee was set, so both the Administrator and the Manager get notified.
    expect(Notification::where('user_id', $admin->id)->count())->toBe(1);
    expect(Notification::where('user_id', $manager->id)->count())->toBe(1);
    expect(Notification::where('user_id', $member->id)->count())->toBe(0);
});

it('only lists requests belonging to the authenticated user organization', function () {
    [, $adminA] = makeOrgWithUsers();
    [, $adminB] = makeOrgWithUsers();

    Request::factory()->create(['organization_id' => $adminA->organization_id, 'created_by' => $adminA->id, 'title' => 'Org A request']);
    Request::factory()->create(['organization_id' => $adminB->organization_id, 'created_by' => $adminB->id, 'title' => 'Org B request']);

    $response = $this->actingAs($adminA)->getJson('/api/requests');

    $titles = collect($response->json('data'))->pluck('title');

    expect($titles)->toContain('Org A request');
    expect($titles)->not->toContain('Org B request');
});

it('shows a request with its comments and status history', function () {
    [$organization, $admin] = makeOrgWithUsers();
    $request = Request::factory()->create(['organization_id' => $organization->id, 'created_by' => $admin->id]);
    $request->comments()->create(['organization_id' => $organization->id, 'user_id' => $admin->id, 'body' => 'Looking into it']);

    $response = $this->actingAs($admin)->getJson("/api/requests/{$request->id}");

    $response->assertOk()
        ->assertJsonPath('comments.0.body', 'Looking into it');
});

it('adds a comment to a request', function () {
    [$organization, $admin] = makeOrgWithUsers();
    $request = Request::factory()->create(['organization_id' => $organization->id, 'created_by' => $admin->id]);

    $response = $this->actingAs($admin)->postJson("/api/requests/{$request->id}/comments", [
        'body' => 'On it, thanks!',
    ]);

    $response->assertCreated()->assertJsonPath('body', 'On it, thanks!');
    expect($request->comments()->count())->toBe(1);
});

it('lets a manager move a request through valid status transitions', function () {
    [$organization, , $manager] = makeOrgWithUsers();
    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $manager->id,
        'status' => RequestStatus::Draft,
    ]);

    $this->actingAs($manager)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Open->value])
        ->assertOk()
        ->assertJsonPath('status', RequestStatus::Open->value);

    expect($request->statusHistories()->count())->toBe(1);
    expect($request->fresh()->status)->toBe(RequestStatus::Open);
});

it('rejects an invalid status transition', function () {
    [$organization, , $manager] = makeOrgWithUsers();
    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $manager->id,
        'status' => RequestStatus::Draft,
    ]);

    $this->actingAs($manager)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Closed->value])
        ->assertUnprocessable();

    expect($request->fresh()->status)->toBe(RequestStatus::Draft);
});

it('forbids a member from changing the status of a request not assigned to them', function () {
    [$organization, , , $member] = makeOrgWithUsers();
    $otherMember = User::factory()->create(['organization_id' => $organization->id, 'role' => UserRole::Member]);
    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $otherMember->id,
        'status' => RequestStatus::Draft,
    ]);

    $this->actingAs($member)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Open->value])
        ->assertForbidden();
});
