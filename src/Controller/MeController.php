<?php
namespace App\Controller;

use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/api')]
final class MeController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $email = method_exists($user, 'getEmail') ? (string) $user->getEmail() : '';
        $login = $email && str_contains($email, '@') ? explode('@', $email)[0] : 'user';

        // Tes champs sur User
        $name     = method_exists($user, 'getName')     ? $user->getName()     : null;
        $lastname = method_exists($user, 'getLastname') ? $user->getLastname() : null;

        // Fallback via Student/Professor si besoin
        if ((!$name || !$lastname) && method_exists($user, 'getStudent') && $user->getStudent()) {
            $s = $user->getStudent();
            if (!$name     && method_exists($s, 'getName'))     $name     = $s->getName();
            if (!$lastname && method_exists($s, 'getLastname')) $lastname = $s->getLastname();
        }
        if ((!$name || !$lastname) && method_exists($user, 'getProfessor') && $user->getProfessor()) {
            $p = $user->getProfessor();
            if (!$name     && method_exists($p, 'getName'))     $name     = $p->getName();
            if (!$lastname && method_exists($p, 'getLastname')) $lastname = $p->getLastname();
        }

        $fullName    = trim(sprintf('%s %s', (string) $name, (string) $lastname));
        $displayName = $fullName !== '' ? $fullName : $login;

        return $this->json([
            'id'          => method_exists($user, 'getId') ? $user->getId() : null,
            'email'       => $email,
            'roles'       => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'name'        => $name,       // prénom
            'lastname'    => $lastname,   // nom
            'fullName'    => $fullName !== '' ? $fullName : null,
            'displayName' => $displayName,
        ]);
    }

}
