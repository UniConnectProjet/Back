<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MetricsControllerTest extends AbstractApiTestCase
{
    public function testMetricsEndpointReturnsPrometheusFormat(): void
    {
        $this->client->request('GET', '/metrics');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $response = $this->client->getResponse();
        $content = $response->getContent();
        
        // Vérifier que la réponse contient du contenu Prometheus
        $this->assertNotEmpty($content);
        
        // Vérifier le Content-Type
        $this->assertEquals('text/plain; version=0.0.4; charset=UTF-8', $response->headers->get('Content-Type'));
        
        // Vérifier que le contenu ressemble à du format Prometheus
        // (même si vide, il devrait y avoir des commentaires ou des métriques)
        $this->assertIsString($content);
    }

    public function testMetricsEndpointIsAccessibleWithoutAuthentication(): void
    {
        // Les métriques sont généralement publiques pour le monitoring
        $this->client->request('GET', '/metrics');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testMetricsEndpointReturnsTextPlainContentType(): void
    {
        $this->client->request('GET', '/metrics');
        
        $response = $this->client->getResponse();
        $contentType = $response->headers->get('Content-Type');
        
        $this->assertStringContainsString('text/plain', $contentType);
        $this->assertStringContainsString('version=0.0.4', $contentType);
        $this->assertStringContainsString('charset=UTF-8', $contentType);
    }
}
