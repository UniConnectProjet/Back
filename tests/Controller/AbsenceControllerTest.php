<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class AbsenceControllerTest extends AbstractApiTestCase
{
    public function test_list_absences_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/absences/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_update_unknown_absence_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('PUT', '/api/absences/999999', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_delete_unknown_absence_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/absences/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_create_absence_for_unknown_student_or_semester_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('POST', '/api/absences/student/999999/semester/888888', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}