<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;

class AuthControllerTest extends AbstractApiTestCase
{
    public function test_protected_endpoint_requires_auth(): void
    {
        $this->client->request('GET', '/api/protected');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [401, 403]);
    }
}