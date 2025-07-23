<?php

namespace App\Tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestUserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->hasher->hashPassword($user, 'test'));
        $user->setRoles(['ROLE_USER']);
        $user->setName('Test');
        $user->setLastname('User');
        $user->setBirthday(new \DateTime('2000-01-01'));

        $manager->persist($user);
        $manager->flush();
    }
}