<?php
namespace App\Controller;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[Route('/metrics', name: 'metrics')]
    public function __invoke(): Response
    {
        $renderer = new RenderTextFormat();
        $metrics  = $this->registry->getMetricFamilySamples();

        return new Response(
            $renderer->render($metrics),
            200,
            ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }
}
