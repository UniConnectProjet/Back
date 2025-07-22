<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testSetAndGetName(): void
    {
        $user = new User();
        $user->setName('Yassine');
        $this->assertEquals('Yassine', $user->getName());
    }

    public function testSetAndGetLastname(): void
    {
        $user = new User();
        $user->setLastname('Ibourhim');
        $this->assertEquals('Ibourhim', $user->getLastname());
    }

    public function testSetAndGetBirthday(): void
    {
        $user = new User();
        $date = new \DateTime('2000-01-01');
        $user->setBirthday($date);

        $this->assertEquals($date, $user->getBirthday());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashed_password');

        $this->assertEquals('hashed_password', $user->getPassword());
    }

    public function testRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // ROLE_USER est toujours ajouté
    }
}