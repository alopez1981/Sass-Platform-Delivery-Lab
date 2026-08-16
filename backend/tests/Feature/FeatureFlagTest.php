<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Features\MembersCanCloseOwnRequests;
use App\Models\Request;
use App\Models\User;
use Laravel\Pennant\Feature;

it('lists feature flags as inactive by default', function () {
    [, $admin] = makeOrgWithUsers();

    $this->actingAs($admin)
        ->getJson('/api/feature-flags')
        ->assertOk()
        ->assertJsonFragment(['key' => 'members-can-close-own-requests', 'active' => false]);
});

it('forbids a non-administrator from toggling a feature flag', function () {
    [, , $manager] = makeOrgWithUsers();

    $this->actingAs($manager)
        ->patchJson('/api/feature-flags/members-can-close-own-requests', ['active' => true])
        ->assertForbidden();
});

it('lets an administrator activate a flag for their own organization only', function () {
    [$organizationA, $adminA] = makeOrgWithUsers();
    [$organizationB] = makeOrgWithUsers();

    $this->actingAs($adminA)
        ->patchJson('/api/feature-flags/members-can-close-own-requests', ['active' => true])
        ->assertOk()
        ->assertJson(['key' => 'members-can-close-own-requests', 'active' => true]);

    expect(Feature::for($organizationA)->active(MembersCanCloseOwnRequests::class))->toBeTrue();
    expect(Feature::for($organizationB)->active(MembersCanCloseOwnRequests::class))->toBeFalse();
});

it('lets a member close their own request only once the flag is active for their organization', function () {
    [$organization, , , $member] = makeOrgWithUsers();
    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $member->id,
        'status' => RequestStatus::Resolved,
    ]);

    $this->actingAs($member)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Closed->value])
        ->assertForbidden();

    Feature::for($organization)->activate(MembersCanCloseOwnRequests::class);

    $this->actingAs($member)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Closed->value])
        ->assertOk()
        ->assertJsonPath('status', RequestStatus::Closed->value);
});

it('does not let a member close a request created by someone else, even with the flag active', function () {
    [$organization, , , $member] = makeOrgWithUsers();
    $otherMember = User::factory()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::Member,
    ]);
    $request = Request::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => $otherMember->id,
        'status' => RequestStatus::Resolved,
    ]);

    Feature::for($organization)->activate(MembersCanCloseOwnRequests::class);

    $this->actingAs($member)
        ->patchJson("/api/requests/{$request->id}/status", ['status' => RequestStatus::Closed->value])
        ->assertForbidden();
});
