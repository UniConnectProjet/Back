<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Entity\User;
use App\Repository\ProfessorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/professors')]
final class ProfessorCrudController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}


    private function getProfessorUser(Professor $p): ?User
    {
        if (method_exists($p, 'getUser')) return $p->getUser();
        if (method_exists($p, 'getUserId')) return $p->getUserId();
        return null;
    }

    private function setProfessorUser(Professor $p, User $u): void
    {
        if (method_exists($p, 'setUser')) { $p->setUser($u); return; }
        if (method_exists($p, 'setUserId')) { $p->setUserId($u); return; }
        throw new \LogicException('Professor has no setUser()/setUserId()');
    }

    private function getWeeklyAvailability(Professor $p): mixed
    {
        return method_exists($p, 'getWeeklyAvailability') ? $p->getWeeklyAvailability() : null;
    }

    private function setWeeklyAvailability(Professor $p, mixed $value): void
    {
        if (method_exists($p, 'setWeeklyAvailability')) {
            // accepte array|null|string (si string JSON)
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) $value = $decoded;
            }
            $p->setWeeklyAvailability($value);
        }
    }

    private function denyUnlessAdminOrOwner(Professor $p): void
    {
        if ($this->isGranted('ROLE_ADMIN')) return;
        $me = $this->getUser();
        $owner = $this->getProfessorUser($p);
        if (!$me || !$owner || $me->getId() !== $owner->getId()) {
            throw $this->createAccessDeniedException('Not allowed.');
        }
    }

    private function normalizeProfessor(Professor $p): array
    {
        $u = $this->getProfessorUser($p);
        return [
            'id' => $p->getId(),
            'user' => $u ? [
                'id' => $u->getId(),
                'name' => method_exists($u, 'getName') ? $u->getName() : null,
                'lastname' => method_exists($u, 'getLastname') ? $u->getLastname() : null,
                'email' => method_exists($u, 'getEmail') ? $u->getEmail() : null,
            ] : null,
            'weeklyAvailability' => $this->getWeeklyAvailability($p),
        ];
    }

    // LIST (ADMIN)
    #[Route('', name: 'prof_index', methods: ['GET'])]
    public function index(ProfessorRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $list = array_map(fn(Professor $p) => $this->normalizeProfessor($p), $repo->findAll());
        return $this->json($list);
    }

    // GET (ADMIN ou propriétaire)
    #[Route('/{id}', name: 'prof_show', methods: ['GET'])]
    public function getProfessor(Professor $professor): JsonResponse
    {
        $this->denyUnlessAdminOrOwner($professor);
        return $this->json($this->normalizeProfessor($professor));
    }

    // CREATE (ADMIN)
    #[Route('', name: 'prof_create', methods: ['POST'])]
    public function create(
        Request $request,
        ProfessorRepository $profRepo,
        UserRepository $userRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $payload = $request->toArray();
        $userId  = $payload['userId'] ?? null;
        if (!$userId) {
            return $this->json(['error' => 'userId is required'], 400);
        }

        /** @var User|null $user */
        $user = $userRepo->find((int)$userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $existing = $profRepo->findOneBy(['userId' => $user]) ?? $profRepo->findOneBy(['user' => $user]);
        if ($existing) {
            return $this->json(['error' => 'Professor already exists for this user'], 409);
        }

        $prof = new Professor();
        $this->setProfessorUser($prof, $user);
        if (array_key_exists('weeklyAvailability', $payload)) {
            $this->setWeeklyAvailability($prof, $payload['weeklyAvailability']);
        }

        // s’assurer du rôle
        if (method_exists($user, 'getRoles') && method_exists($user, 'setRoles')) {
            $roles = $user->getRoles();
            if (!in_array('ROLE_PROFESSOR', $roles, true)) {
                $roles[] = 'ROLE_PROFESSOR';
                $user->setRoles(array_values(array_unique($roles)));
                $this->em->persist($user);
            }
        }

        $this->em->persist($prof);
        $this->em->flush();

        return $this->json($this->normalizeProfessor($prof), 201);
    }

    #[Route('/{id}', name: 'prof_update', methods: ['PATCH', 'PUT'])]
    public function update(
        Professor $professor,
        Request $request,
        UserRepository $userRepo,
        ProfessorRepository $profRepo
    ): JsonResponse {
        $this->denyUnlessAdminOrOwner($professor);

        $payload = $request->toArray();

        if (array_key_exists('weeklyAvailability', $payload)) {
            $this->setWeeklyAvailability($professor, $payload['weeklyAvailability']);
        }

        if ($this->isGranted('ROLE_ADMIN') && array_key_exists('userId', $payload)) {
            $newUser = $userRepo->find((int)$payload['userId']);
            if (!$newUser) return $this->json(['error' => 'User not found'], 404);

            $exists = $profRepo->findOneBy(['userId' => $newUser]) ?? $profRepo->findOneBy(['user' => $newUser]);
            if ($exists && $exists->getId() !== $professor->getId()) {
                return $this->json(['error' => 'Another Professor already linked to this user'], 409);
            }
            $this->setProfessorUser($professor, $newUser);
        }

        $this->em->flush();
        return $this->json($this->normalizeProfessor($professor));
    }

    // DELETE (ADMIN)
    #[Route('/{id}', name: 'prof_delete', methods: ['DELETE'])]
    public function delete(Professor $professor): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->em->remove($professor);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }
}
