<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends AbstractApiTestCase
{
    public function test_list_courses_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/courses/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_get_courses_by_student_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/courses/student/999999');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function test_get_courses_by_course_unit_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/courses/courseUnit/999999');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function test_update_unknown_course_returns_404(): void
    {
        $this->authenticate();
        $this->jsonRequest('PUT', '/api/courses/999999', []);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_delete_unknown_course_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/courses/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
