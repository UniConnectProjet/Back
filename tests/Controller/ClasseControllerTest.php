<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ClasseControllerTest extends AbstractApiTestCase
{
    public function test_list_classes_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/classes/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_get_unknown_class_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/classes/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_add_student_to_unknown_class_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/classes/999999/students/123456');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_add_unknown_student_to_class_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/classes/123456/students/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_add_student_to_class_ok(): void
    {
        $this->authenticate();
        $class = $this->em->getRepository(\App\Entity\Classe::class)->findOneBy([]);
        $student = $this->em->getRepository(\App\Entity\Student::class)->findOneBy([]);
        $this->assertNotNull($class);
        $this->assertNotNull($student);

        $this->client->request('POST', '/api/classes/'.$class->getId().'/students/'.$student->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
