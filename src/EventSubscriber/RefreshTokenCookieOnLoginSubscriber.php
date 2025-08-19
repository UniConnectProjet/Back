<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Cookie;

final class RefreshTokenCookieOnLoginSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $req = $event->getRequest();
        // adapte si ton chemin est différent
        if ($req->getPathInfo() !== '/api/login_check' || $req->getMethod() !== 'POST') return;

        $res = $event->getResponse();
        $data = json_decode($res->getContent() ?: '[]', true);
        if (!is_array($data) || !isset($data['refresh_token'])) return;

        $refresh = (string) $data['refresh_token'];

        // on n’expose pas le refresh au front
        unset($data['refresh_token']);
        $res->setContent(json_encode($data));

        $secure  = $req->isSecure();
        $name    = $secure ? '__Host-refresh' : 'refresh';
        $expires = (new \DateTimeImmutable('+14 days'));

        $cookie = new Cookie(
            $name,
            $refresh,
            $expires,  
            '/',          // path
            null,         // domain
            $secure,      // secure
            true,         // httpOnly
            false,        // raw
            'Strict'      // sameSite
        );

        $res->headers->setCookie($cookie);
    }
}
