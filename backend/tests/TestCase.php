<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every API request in this app is meant to come from the SPA. Sending
     * a matching Origin header on every test request makes Sanctum treat it
     * as a stateful frontend request (session + CSRF middleware included),
     * the same way it behaves for the real Vue app — see ADR 0002.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', config('app.frontend_url'));
    }
}
