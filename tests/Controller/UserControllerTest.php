<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractApiTestCase
{
    public function test_list_users_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/users/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_get_user_by_email_requires_auth_and_may_401(): void
    {
        $this->client->request('GET', '/api/users/someone@example.com');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_create_user_invalid_payload_returns_400_or_422(): void
    {
        $this->authenticate();
        $this->jsonRequest('POST', '/api/users/', []);
        $this->assertContains($this->client->getResponse()->getStatusCode(), [Response::HTTP_BAD_REQUEST, 422]);
    }

    public function test_update_unknown_user_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('PUT', '/api/users/999999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name'      => 'Ghost',
            'lastname'  => 'User',
            'email'     => 'ghost@example.com',
            'password'  => 'secret123',
            'birthday'  => '2000-01-01',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function test_delete_unknown_user_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/users/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}