<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuthControllerTest extends AbstractApiTestCase
{
    private function createTestUser(EntityManagerInterface $em, string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email . '+' . uniqid())
             ->setRoles($roles)
             ->setPassword(password_hash('password', PASSWORD_BCRYPT))
             ->setName('Test')
             ->setLastname('User')
             ->setBirthday(new \DateTime('1990-01-01'));
        
        $em->persist($user);
        $em->flush();
        
        return $user;
    }

    public function test_protected_endpoint_requires_auth(): void
    {
        $this->client->request('GET', '/api/protected');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_protected_endpoint_with_authenticated_user(): void
    {
        $user = $this->createTestUser($this->em, 'test@example.com', ['ROLE_USER']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/protected');
        $this->assertResponseIsSuccessful();
        
        $response = $this->decodeJson();
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('You have access to this protected route!', $response['message']);
    }

    public function test_student_endpoint_requires_student_role(): void
    {
        $this->client->request('GET', '/api/student');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_student_endpoint_with_student_role(): void
    {
        $user = $this->createTestUser($this->em, 'student@example.com', ['ROLE_STUDENT']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/student');
        $this->assertResponseIsSuccessful();
        
        $response = $this->decodeJson();
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Hello student!', $response['message']);
    }

    public function test_student_endpoint_with_non_student_role(): void
    {
        $user = $this->createTestUser($this->em, 'prof@example.com', ['ROLE_PROFESSOR']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/student');
        $this->assertResponseStatusCodeSame(403);
    }

    public function test_professor_endpoint_requires_professor_role(): void
    {
        $this->client->request('GET', '/api/professor');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_professor_endpoint_with_professor_role(): void
    {
        $user = $this->createTestUser($this->em, 'prof@example.com', ['ROLE_PROFESSOR']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/professor');
        $this->assertResponseIsSuccessful();
        
        $response = $this->decodeJson();
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Hello professor!', $response['message']);
    }

    public function test_professor_endpoint_with_non_professor_role(): void
    {
        $user = $this->createTestUser($this->em, 'student@example.com', ['ROLE_STUDENT']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/professor');
        $this->assertResponseStatusCodeSame(403);
    }

    public function test_me_endpoint_requires_auth(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [401, 403]));
    }

    public function test_me_endpoint_with_authenticated_user(): void
    {
        $user = $this->createTestUser($this->em, 'me@example.com', ['ROLE_USER']);
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/api/me');
        $this->assertResponseIsSuccessful();
        
        $response = $this->decodeJson();
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('roles', $response);
        $this->assertStringStartsWith('me@example.com', $response['email']);
        $this->assertEquals(['ROLE_USER'], $response['roles']);
    }
}