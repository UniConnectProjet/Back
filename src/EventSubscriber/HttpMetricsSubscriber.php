<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Prometheus\CollectorRegistry;

final class HttpMetricsSubscriber implements EventSubscriberInterface
{
    public function __construct(private CollectorRegistry $registry) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $req = $event->getRequest();
        $res = $event->getResponse();

        $counter = $this->registry->getOrRegisterCounter(
            'http', 'requests_total',
            'Total HTTP requests',
            ['method','route','code']
        );

        $route = $req->attributes->get('_route') ?? 'unknown';
        $counter->inc([strtoupper($req->getMethod()), $route, (string)$res->getStatusCode()]);
    }
}