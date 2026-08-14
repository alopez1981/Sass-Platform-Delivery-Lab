<?php

use App\Models\Organization;
use App\Models\User;

it('logs in a user with valid credentials', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'ada@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonPath('user.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/logout')
        ->assertNoContent();

    // Explicitly "web": the auth:sanctum middleware switches the app's
    // *default* guard to "sanctum" for the rest of the request, so
    // assertGuest() with no argument would check the wrong guard here.
    $this->assertGuest('web');
});

it('rejects unauthenticated access to protected routes', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('stays authenticated across requests after a real session login', function () {
    // Deliberately does NOT use actingAs(): that helper injects the user
    // straight into the guard and skips the "load the User model by ID
    // from the session" step that a real second request performs. That
    // step once caused an infinite-recursion 500 (User's own query being
    // filtered by the organization scope, which itself needs the — not
    // yet resolved — authenticated user). See ADR 0003's amendment.
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'ada@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'ada@example.com');

    $this->getJson('/api/requests')->assertOk();
});
