<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;

class MeControllerTest extends AbstractApiTestCase
{
    public function test_me_requires_auth(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [401, 403]);
    }
}
