<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class InjectRefreshFromCookieSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $req = $event->getRequest();
        if ($req->getPathInfo() !== '/api/token/refresh' || $req->getMethod() !== 'POST') return;

        if ($req->request->has('refresh_token')) return;

        $cookie = $req->cookies->get('__Host-refresh') ?? $req->cookies->get('refresh');
        if ($cookie) {
            $req->request->set('refresh_token', $cookie);
        }
    }
}