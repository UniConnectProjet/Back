<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Repository\ProfessorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/professors')]
class ProfessorController extends AbstractController
{
    private function isOwner(Professor $prof): bool
    {
        $owner = null;
        if (method_exists($prof, 'getUserId')) {
            $owner = $prof->getUserId();
        } elseif (method_exists($prof, 'getUser')) {
            $owner = $prof->getUser();
        }
        return $owner && $this->getUser() && $owner->getId() === $this->getUser()->getId();
    }

    private function canViewOrEdit(Professor $prof): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $this->isOwner($prof);
    }

    #[Route('/me/professor', name: 'professor.me', methods: ['GET'])]
    public function getMyProfessor(ProfessorRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $professor = $repo->findOneBy(['userId' => $user]);
        if (!$professor) {
            return new JsonResponse(['message' => 'No professor for this user'], 404);
        }

        $email = method_exists($user, 'getEmail') ? (string) $user->getEmail() : '';
        $name = method_exists($user, 'getName') ? $user->getName() : null;
        $lastname = method_exists($user, 'getLastname') ? $user->getLastname() : null;

        if ((!$name || !$lastname) && method_exists($professor, 'getName')) {
            if (!$name) $name = $professor->getName();
            if (!$lastname) $lastname = $professor->getLastname();
        }

        $fullName = trim(sprintf('%s %s', (string) $name, (string) $lastname));
        $displayName = $fullName !== '' ? $fullName : (str_contains($email, '@') ? explode('@', $email)[0] : 'user');

        return new JsonResponse([
            'id'          => $user->getId(),
            'email'       => $email,
            'roles'       => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'name'        => $name,
            'lastname'    => $lastname,
            'fullName'    => $fullName !== '' ? $fullName : null,
            'displayName' => $displayName,
            'professor'   => [
                'id'                  => $professor->getId(),
                'weeklyAvailability'  => method_exists($professor, 'getWeeklyAvailability') ? $professor->getWeeklyAvailability() : null,
                'isActive'            => method_exists($professor, 'isIsActive') ? $professor->isIsActive() : (method_exists($professor, 'getIsActive') ? $professor->getIsActive() : null),
            ],
        ], 200);
    }

    #[Route('', name: 'prof_index', methods: ['GET'])]
    public function index(ProfessorRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $rows = array_map(function (Professor $p) {
            $u = method_exists($p, 'getUserId') ? $p->getUserId() : (method_exists($p, 'getUser') ? $p->getUser() : null);
            return [
                'id' => $p->getId(),
                'userId' => $u?->getId(),
                'weeklyAvailability' => method_exists($p, 'getWeeklyAvailability') ? $p->getWeeklyAvailability() : null,
                'isActive' => method_exists($p, 'isIsActive') ? $p->isIsActive() : (method_exists($p, 'getIsActive') ? $p->getIsActive() : null),
            ];
        }, $repo->findAll());

        return $this->json($rows);
    }

    #[Route('/{id}', name: 'prof_show', methods: ['GET'])]
    public function show(Professor $prof): JsonResponse
    {
        if (!$this->canViewOrEdit($prof)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $u = method_exists($prof, 'getUserId') ? $prof->getUserId() : (method_exists($prof, 'getUser') ? $prof->getUser() : null);

        return $this->json([
            'id' => $prof->getId(),
            'userId' => $u?->getId(),
            'weeklyAvailability' => method_exists($prof, 'getWeeklyAvailability') ? $prof->getWeeklyAvailability() : null,
            'isActive' => method_exists($prof, 'isIsActive') ? $prof->isIsActive() : (method_exists($prof, 'getIsActive') ? $prof->getIsActive() : null),
        ]);
    }

    #[Route('', name: 'prof_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepo,
        ProfessorRepository $profRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = $request->toArray();
        $userId = $data['userId'] ?? null;
        if (!$userId) {
            return $this->json(['error' => 'userId is required'], 422);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // ⚠️ clé correcte : userId (et non "user")
        if ($profRepo->findOneBy(['userId' => $user])) {
            return $this->json(['error' => 'Professor already exists for this user'], 409);
        }

        $prof = new Professor();
        if (method_exists($prof, 'setUserId')) {
            $prof->setUserId($user);
        } elseif (method_exists($prof, 'setUser')) {
            $prof->setUser($user);
        }

        if (isset($data['weeklyAvailability']) && method_exists($prof, 'setWeeklyAvailability')) {
            $prof->setWeeklyAvailability($data['weeklyAvailability']);
        }
        if (array_key_exists('isActive', $data) && method_exists($prof, 'setIsActive')) {
            $prof->setIsActive((bool) $data['isActive']);
        }

        $em->persist($prof);
        $em->flush();

        return $this->json(['id' => $prof->getId()], 201);
    }

    #[Route('/{id}', name: 'prof_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Professor $prof,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->canViewOrEdit($prof)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->toArray();

        if (isset($data['weeklyAvailability']) && method_exists($prof, 'setWeeklyAvailability')) {
            $prof->setWeeklyAvailability($data['weeklyAvailability']);
        }
        if (array_key_exists('isActive', $data) && method_exists($prof, 'setIsActive')) {
            $prof->setIsActive((bool) $data['isActive']);
        }

        $em->flush();

        $u = method_exists($prof, 'getUserId') ? $prof->getUserId() : (method_exists($prof, 'getUser') ? $prof->getUser() : null);

        return $this->json([
            'id' => $prof->getId(),
            'userId' => $u?->getId(),
            'weeklyAvailability' => method_exists($prof, 'getWeeklyAvailability') ? $prof->getWeeklyAvailability() : null,
            'isActive' => method_exists($prof, 'isIsActive') ? $prof->isIsActive() : (method_exists($prof, 'getIsActive') ? $prof->getIsActive() : null),
        ]);
    }

    #[Route('/{id}', name: 'prof_delete', methods: ['DELETE'])]
    public function delete(Professor $prof, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($prof);
        $em->flush();

        return $this->json(['deleted' => true]);
    }
}