<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class LevelControllerTest extends AbstractApiTestCase
{
    public function testGetAllLevels(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/levels/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetOneLevel(): void
    {
        $this->authenticate();
        $level = $this->em->getRepository(\App\Entity\Level::class)->findOneBy([]);
        $this->assertNotNull($level);

        $this->client->request('GET', '/api/levels/'.$level->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetClassesFromLevel(): void
    {
        $this->authenticate();
        $level = $this->em->getRepository(\App\Entity\Level::class)->findOneBy([]);
        $this->assertNotNull($level);

        $this->client->request('GET', '/api/levels/'.$level->getId().'/classes');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetNonexistentLevel(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/levels/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Level not found']),
            $this->client->getResponse()->getContent()
        );
    }
}