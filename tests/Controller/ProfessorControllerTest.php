<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Professor;

final class ProfessorControllerTest extends WebTestCase
{
    /** Génère un email unique pour éviter les collisions UNIQ_IDENTIFIER_EMAIL */
    private function uniq(string $prefix): string
    {
        return sprintf('%s+%s@example.com', $prefix, bin2hex(random_bytes(4)));
    }

    /** Crée un user prêt à l’emploi */
    private function makeUser(EntityManagerInterface $em, string $prefix, array $roles): User
    {
        $u = (new User())
            ->setEmail($this->uniq($prefix))
            ->setRoles($roles)
            ->setPassword(password_hash('pwd', PASSWORD_BCRYPT))
            ->setName(ucfirst($prefix))
            ->setLastname('User')
            ->setBirthday(new \DateTime('1990-01-01'));
        $em->persist($u);
        return $u;
    }

    /** Crée un professor lié à un user (setUserId() ou setUser()) */
    private function makeProfessor(EntityManagerInterface $em, User $user): Professor
    {
        $p = new Professor();
        if (method_exists($p, 'setUserId')) {
            $p->setUserId($user);
        } elseif (method_exists($p, 'setUser')) {
            $p->setUser($user);
        }
        if (method_exists($p, 'setWeeklyAvailability')) {
            $p->setWeeklyAvailability(['MON' => [['08:00', '12:00']]]);
        }
        $em->persist($p);
        return $p;
    }

    public function testIndexAsAdminListsProfessors(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->makeUser($em, 'admin', ['ROLE_ADMIN']);
        $u1 = $this->makeUser($em, 'profA', ['ROLE_PROFESSOR']);
        $u2 = $this->makeUser($em, 'profB', ['ROLE_PROFESSOR']);
        $this->makeProfessor($em, $u1);
        $this->makeProfessor($em, $u2);
        $em->flush();

        $client->loginUser($admin);
        $client->request('GET', '/api/professors');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($list);
        self::assertGreaterThanOrEqual(2, count($list));
    }

    public function testShowAsOwnerIsAllowed(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        $profUser = $this->makeUser($em, 'owner', ['ROLE_PROFESSOR']);
        $prof     = $this->makeProfessor($em, $profUser);
        $em->flush();

        $client->loginUser($profUser);
        $client->request('GET', '/api/professors/'.$prof->getId());

        // Le contrôleur autorise ADMIN ou le "owner" (prof lié) → 200 attendu
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($prof->getId(), $data['id'] ?? null);
    }

    public function testCreateAsAdmin(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        $admin   = $this->makeUser($em, 'admin2', ['ROLE_ADMIN']);
        $newUser = $this->makeUser($em, 'newProf', ['ROLE_PROFESSOR']);
        $em->flush();

        $client->loginUser($admin);
        $payload = [
            'userId' => $newUser->getId(),
            'weeklyAvailability' => ['TUE' => [['10:00','12:00']]],
        ];
        $client->request(
            'POST',
            '/api/professors',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($data['id'] ?? null);

        // Vérifie que le Professor est bien créé en base
        $created = $em->getRepository(Professor::class)->find($data['id']);
        self::assertNotNull($created);
    }

    public function testUpdateWeeklyAvailabilityAsOwner(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        $profUser = $this->makeUser($em, 'owner2', ['ROLE_PROFESSOR']);
        $prof     = $this->makeProfessor($em, $profUser);
        $em->flush();

        $client->loginUser($profUser);
        $payload = [
            'weeklyAvailability' => [
                'WED' => [['09:00','11:00'], ['14:00','16:00']],
            ],
        ];

        $client->request(
            'PUT',
            '/api/professors/'.$prof->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('weeklyAvailability', $data);
        self::assertArrayHasKey('WED', $data['weeklyAvailability']);
    }

    public function testDeleteAsAdmin(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->makeUser($em, 'admin3', ['ROLE_ADMIN']);
        $u     = $this->makeUser($em, 'toDelete', ['ROLE_PROFESSOR']);
        $prof  = $this->makeProfessor($em, $u);
        $em->flush();

        // capture l'ID avant le DELETE
        $id = $prof->getId();

        $client->loginUser($admin);
        $client->request('DELETE', '/api/professors/'.$id);

        self::assertResponseIsSuccessful();

        // purge le contexte pour éviter les états Doctrine incohérents
        $em->clear();

        // vérifie que l'entité n'existe plus
        $deleted = $em->getRepository(Professor::class)->find($id);
        self::assertNull($deleted);

        // (optionnel) vérifie la réponse JSON du contrôleur
        $payload = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($payload['deleted'] ?? false);
    }

}
