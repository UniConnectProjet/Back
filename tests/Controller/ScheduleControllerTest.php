<?php

namespace App\Tests\Controller;

use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ScheduleControllerTest extends AbstractApiTestCase
{
    public function test_next_day_schedule_unknown_student_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/999999/schedule/next-day');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_schedule_range_unknown_student_returns_404(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/999999/schedule?from=2024-01-01&to=2024-01-07');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_next_day_schedule_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/1/schedule/next-day');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_schedule_range_ok(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/1/schedule?from=2024-01-01&to=2024-01-07');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsArray($this->decodeJson());
    }

    public function test_schedule_range_invalid_dates_returns_400(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/1/schedule?from=invalid-date&to=2024-01-07');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function test_schedule_range_missing_dates_returns_400(): void
    {
        $this->authenticate();
        $this->client->request('GET', '/api/students/1/schedule');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
