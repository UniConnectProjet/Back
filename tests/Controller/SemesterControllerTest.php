<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class SemesterControllerTest extends AbstractApiTestCase
{
    public function test_list_semesters_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/semesters/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }
}