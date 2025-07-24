<?php

namespace App\Tests\DataFixtures;

use App\Entity\User;
use App\Entity\Classe;
use App\Entity\Level;
use App\Entity\Category;
use App\Entity\Student;
use App\Entity\Semester;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Niveau
        $level = new Level();
        $level->setName('Bac+1');
        $manager->persist($level);

        // Catégorie
        $category = new Category();
        $category->setName('Informatique');
        $category->addLevelId($level);
        $manager->persist($category);

        // Classe
        $classe = new Classe();
        $classe->setName('I1');
        $classe->setLevelId($level);
        $classe->setCategory($category);
        $manager->persist($classe);

        // Semestre
        $semester = new Semester();
        $semester->setName('S1');
        $semester->setStartDate(new \DateTime('2024-01-01'));
        $semester->setEndDate(new \DateTime('2024-06-30'));
        $manager->persist($semester);

        // Utilisateur
        $user = new User();
        $user->setName('Test');
        $user->setLastname('User');
        $user->setEmail('test@example.com');
        $user->setBirthday(new \DateTime('2000-01-01'));
        $user->setPassword($this->hasher->hashPassword($user, 'test'));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);

        // Création du Student
        $student = new Student();
        $student->setUser($user);
        $student->setClasse($classe);
        $student->addSemester($semester);
        $manager->persist($student);

        $manager->flush();
    }
}