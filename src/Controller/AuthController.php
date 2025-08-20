<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api')]
final class AuthController extends AbstractController
{
    #[Route('/protected', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function protectedRoute(): JsonResponse
    {
        return new JsonResponse(['message' => 'You have access to this protected route!']);
    }

    #[Route('/student', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function studentOnly(): JsonResponse
    {
        return new JsonResponse(['message' => 'Hello student!']);
    }

    #[Route('/professor', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function professorOnly(): JsonResponse
    {
        return new JsonResponse(['message' => 'Hello professor!']);
    }

    #[Route('/me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        /** @var UserInterface|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'id'    => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request, RefreshTokenRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $cookie = $request->cookies->get('__Host-refresh') ?? $request->cookies->get('refresh');
        if ($cookie) {
            $rt = $repo->findOneBy(['refreshToken' => $cookie]);
            if ($rt) { $em->remove($rt); $em->flush(); }
        }

        $res = new JsonResponse(['ok' => true]);
        $res->headers->clearCookie('__Host-refresh','/');
        $res->headers->clearCookie('refresh','/');
        return $res;
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $req, RefreshTokenService $refresh, JWTTokenManagerInterface $jwt): JsonResponse
    {
        $cookie = $req->cookies->get('__Host-refresh');
        if (!$cookie) {
            return new JsonResponse(['message' => 'Missing refresh cookie'], 401);
        }

        $user = $refresh->validateAndRotate($cookie, $req);
        if (!$user) {
            return new JsonResponse(['message' => 'Invalid refresh'], 401);
        }

        $now = time();
        $access = $jwt->createFromPayload($user, ['iat'=>$now,'exp'=>$now+600]);

        $resp = new JsonResponse([
            'accessToken' => $access,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
        $resp->headers->setCookie(
            Cookie::create('__Host-refresh', $refresh->currentToken(), [
                'path' => '/', 'secure' => true, 'httpOnly' => true, 'sameSite' => 'Strict', 'maxAge' => 1209600
            ])
        );
        return $resp;
    }
}