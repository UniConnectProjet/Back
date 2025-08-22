<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseUnitControllerTest extends AbstractApiTestCase
{
    public function test_list_course_units_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/course/units/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_get_unknown_course_unit_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/course/units/999999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_get_courses_of_unknown_course_unit_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/course/units/999999/modules');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_get_course_units_by_student_returns_200_or_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/course/units/student/999999');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function test_add_course_to_unknown_course_unit_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('POST', '/api/course/units/999999/addCourse/888888');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_remove_course_from_unknown_course_unit_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('DELETE', '/api/course/units/999999/removeModule/888888');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
