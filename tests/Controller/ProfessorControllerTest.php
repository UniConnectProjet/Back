<?php
namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Professor;

final class ProfessorControllerTest extends AbstractApiTestCase
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
        $admin = $this->makeUser($this->em, 'admin', ['ROLE_ADMIN']);
        $u1 = $this->makeUser($this->em, 'profA', ['ROLE_PROFESSOR']);
        $u2 = $this->makeUser($this->em, 'profB', ['ROLE_PROFESSOR']);
        $this->makeProfessor($this->em, $u1);
        $this->makeProfessor($this->em, $u2);
        $this->em->flush();

        $this->client->loginUser($admin);
        $this->client->request('GET', '/api/professors');

        $this->assertResponseIsSuccessful();
        $list = $this->decodeJson();
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(2, count($list));
    }

    public function testShowAsOwnerIsAllowed(): void
    {
        $profUser = $this->makeUser($this->em, 'owner', ['ROLE_PROFESSOR']);
        $prof     = $this->makeProfessor($this->em, $profUser);
        $this->em->flush();

        $this->client->loginUser($profUser);
        $this->client->request('GET', '/api/professors/'.$prof->getId());

        // Le contrôleur autorise ADMIN ou le "owner" (prof lié) → 200 attendu
        $this->assertResponseIsSuccessful();
        $data = $this->decodeJson();
        $this->assertSame($prof->getId(), $data['id'] ?? null);
    }

    public function testCreateAsAdmin(): void
    {
        $admin   = $this->makeUser($this->em, 'admin2', ['ROLE_ADMIN']);
        $newUser = $this->makeUser($this->em, 'newProf', ['ROLE_PROFESSOR']);
        $this->em->flush();

        $this->client->loginUser($admin);
        $payload = [
            'userId' => $newUser->getId(),
            'weeklyAvailability' => ['TUE' => [['10:00','12:00']]],
        ];
        $this->jsonRequest('POST', '/api/professors', $payload);

        $this->assertResponseIsSuccessful();
        $data = $this->decodeJson();
        $this->assertNotEmpty($data['id'] ?? null);

        // Vérifie que le Professor est bien créé en base
        $created = $this->em->getRepository(Professor::class)->find($data['id']);
        $this->assertNotNull($created);
    }

    public function testUpdateWeeklyAvailabilityAsOwner(): void
    {
        $profUser = $this->makeUser($this->em, 'owner2', ['ROLE_PROFESSOR']);
        $prof     = $this->makeProfessor($this->em, $profUser);
        $this->em->flush();

        $this->client->loginUser($profUser);
        $payload = [
            'weeklyAvailability' => [
                'WED' => [['09:00','11:00'], ['14:00','16:00']],
            ],
        ];

        $this->jsonRequest(
            'PUT',
            '/api/professors/'.$prof->getId(),
            $payload
        );

        $this->assertResponseIsSuccessful();
        $data = $this->decodeJson();
        $this->assertArrayHasKey('weeklyAvailability', $data);
        $this->assertArrayHasKey('WED', $data['weeklyAvailability']);
    }

    public function testDeleteAsAdmin(): void
    {
        $admin = $this->makeUser($this->em, 'admin3', ['ROLE_ADMIN']);
        $u     = $this->makeUser($this->em, 'toDelete', ['ROLE_PROFESSOR']);
        $prof  = $this->makeProfessor($this->em, $u);
        $this->em->flush();

        // capture l'ID avant le DELETE
        $id = $prof->getId();

        $this->client->loginUser($admin);
        $this->client->request('DELETE', '/api/professors/'.$id);

        $this->assertResponseIsSuccessful();

        // purge le contexte pour éviter les états Doctrine incohérents
        $this->em->clear();

        // vérifie que l'entité n'existe plus
        $deleted = $this->em->getRepository(Professor::class)->find($id);
        $this->assertNull($deleted);

        // (optionnel) vérifie la réponse JSON du contrôleur
        $payload = $this->decodeJson();
        self::assertTrue($payload['deleted'] ?? false);
    }

}
