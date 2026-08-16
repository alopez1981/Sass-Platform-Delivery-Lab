<?php

it('renders a consistent JSON shape for 401 unauthenticated errors', function () {
    $this->getJson('/api/me')
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});

it('renders a consistent JSON shape for 403 forbidden errors', function () {
    [, , $manager] = makeOrgWithUsers();

    $this->actingAs($manager)
        ->patchJson('/api/feature-flags/members-can-close-own-requests', ['active' => true])
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

it('renders a consistent JSON shape for 404 not-found errors', function () {
    [, $admin] = makeOrgWithUsers();

    $this->actingAs($admin)
        ->getJson('/api/requests/999999')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('renders a consistent JSON shape for 422 validation errors', function () {
    [, $admin] = makeOrgWithUsers();

    $this->actingAs($admin)
        ->postJson('/api/requests', ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['title']]);
});

it('renders JSON errors on /api/* even when the client does not ask for JSON', function () {
    [, $admin] = makeOrgWithUsers();

    // Plain post(), not postJson(): no "Accept: application/json" header.
    // Without shouldRenderJsonWhen() in bootstrap/app.php, Laravel would
    // render this 404 as an HTML error page instead.
    $response = $this->actingAs($admin)->call('GET', '/api/requests/999999');

    $response->assertNotFound();
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('throttles repeated login attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertStatus(429);
});
