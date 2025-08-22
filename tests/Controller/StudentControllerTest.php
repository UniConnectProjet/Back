<?php

namespace App\Tests\Controller;

use App\Entity\Classe;
use App\Entity\User;
use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StudentControllerTest extends AbstractApiTestCase
{
    public function test_list_students_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_me_student_requires_auth(): void
    {
        $this->client->request('GET', '/api/me/student');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [401, 403]);
    }

    public function test_me_grades_requires_auth(): void
    {
        $this->client->request('GET', '/api/me/grades');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [401, 403]);
    }

    public function test_me_semesters_absences_requires_auth(): void
    {
        $this->client->request('GET', '/api/me/semesters/absences');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [401, 403]);
    }

    public function test_get_student_absences_not_found(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/student/999999/absences');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_create_student_returns_2xx(): void
    {
        $this->authenticate();

        $classe = $this->em->getRepository(\App\Entity\Classe::class)->findOneBy([]);
        $this->assertNotNull($classe, 'Une Classe des fixtures est requise');

        $user = $this->em->getRepository(\App\Entity\User::class)->findOneBy([]);
        $this->assertNotNull($user, 'Un User des fixtures est requis');

        $payload = [
            'classeId' => $classe->getId(),
            'userId'   => $user->getId(),
        ];

        $this->jsonRequest('POST', '/api/student', $payload);

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($status, [201, 200], true),
            'POST /api/student doit renvoyer 201/200, reçu '.$status.' avec body: '.$this->client->getResponse()->getContent()
        );

        $this->assertJson($this->client->getResponse()->getContent());
    }


    public function test_update_unknown_student_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('PUT', '/api/students/999999', []);
        $this->assertResponseStatusCodeSame(404);
    }

    public function test_delete_unknown_student_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/students/999999');
        $this->assertResponseStatusCodeSame(404);
    }
}