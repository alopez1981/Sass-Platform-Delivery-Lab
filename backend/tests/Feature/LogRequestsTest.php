<?php

use Illuminate\Support\Facades\Log;

it('assigns a new correlation ID and returns it in the response header', function () {
    $response = $this->getJson('/api/health/live');

    $response->assertOk();
    expect($response->headers->get('X-Correlation-Id'))->not->toBeEmpty();
});

it('reuses an inbound correlation ID instead of generating a new one', function () {
    $response = $this->withHeader('X-Correlation-Id', 'test-correlation-123')
        ->getJson('/api/health/live');

    $response->assertHeader('X-Correlation-Id', 'test-correlation-123');
});

it('logs one structured line per request tagged with the correlation ID', function () {
    Log::spy();

    $this->withHeader('X-Correlation-Id', 'trace-abc')->getJson('/api/health/live');

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'http.request'
                && $context['method'] === 'GET'
                && $context['status'] === 200;
        });
});
