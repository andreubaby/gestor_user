<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CsrfRefreshTest extends TestCase
{
    public function test_guest_can_refresh_csrf_token(): void
    {
        $response = $this->getJson(route('csrf.refresh'));

        $response->assertOk()->assertJsonStructure(['token']);

        $this->assertIsString($response->json('token'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_refresh_endpoint_rotates_token(): void
    {
        $first = $this->getJson(route('csrf.refresh'))->json('token');
        $second = $this->getJson(route('csrf.refresh'))->json('token');

        $this->assertNotSame($first, $second);
    }
}

