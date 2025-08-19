<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RefreshTokenCookieOnLoginSubscriber implements EventSubscriberInterface
{
    private const ACCESS_COOKIE  = 'ACCESS_TOKEN';
    private const REFRESH_COOKIE = 'REFRESH_TOKEN';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $req = $event->getRequest();
        $path = $req->getPathInfo();
        if (!in_array($path, ['/api/login_check', '/api/token/refresh'], true) || $req->getMethod() !== 'POST') {
            return;
        }

        $res = $event->getResponse();
        if ($res->getStatusCode() !== 200) return;

        $payload = json_decode($res->getContent() ?: 'null', true);
        if (!is_array($payload)) return;

        $secure   = $req->isSecure();  // false en dev http://localhost
        $sameSite = 'Lax';             // ✅ pour localhost:3000 ↔ localhost:8000

        // Access token -> cookie 15min
        if (!empty($payload['token'])) {
            $res->headers->setCookie(
                Cookie::create(self::ACCESS_COOKIE, $payload['token'], new \DateTimeImmutable('+15 minutes'))
                    ->withHttpOnly(true)->withSecure($secure)->withPath('/')->withSameSite($sameSite)
            );
            unset($payload['token']);
        }

        if (!empty($payload['refresh_token'])) {
            $res->headers->setCookie(
                Cookie::create(self::REFRESH_COOKIE, $payload['refresh_token'], new \DateTimeImmutable('+14 days'))
                    ->withHttpOnly(true)->withSecure($secure)->withPath('/')->withSameSite($sameSite)
            );
            unset($payload['refresh_token']);
        }

        $res->setContent(json_encode($payload ?: ['ok' => true]));
    }
}