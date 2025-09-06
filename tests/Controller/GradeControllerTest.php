<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class GradeControllerTest extends AbstractApiTestCase
{
    public function test_list_grades_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/grade/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_get_grades_by_student_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/grade/by-student/999999');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
    }

    public function test_get_grades_by_course_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/grade/by-course/999999');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
    }

    public function test_get_grades_by_semester_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/grade/by-semester/999999');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
    }

    public function test_add_grade_for_unknown_student_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('POST', '/api/grade/student/999999', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_add_grade_for_unknown_course_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('POST', '/api/grade/course/999999', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_update_unknown_grade_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('PUT', '/api/grade/999999', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_delete_unknown_grade_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/grade/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}