<?php

it('reports liveness without requiring authentication or touching any dependency', function () {
    $this->getJson('/api/health/live')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

it('reports readiness with database and cache checks, without requiring authentication', function () {
    $response = $this->getJson('/api/health/ready');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('checks.cache', 'ok');
});

it('reports application health with version info and a queue check, without requiring authentication', function () {
    $response = $this->getJson('/api/health/app');

    // The queue check opens a real connection to RabbitMQ, which isn't
    // running in the test environment — it's fine for it to report an
    // error here (asserted loosely below); what matters is the endpoint
    // itself never throws and always reports a structured result.
    $response->assertJsonStructure(['status', 'checks' => ['database', 'cache', 'queue'], 'environment', 'laravel_version', 'php_version'])
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('environment', 'testing');
});
