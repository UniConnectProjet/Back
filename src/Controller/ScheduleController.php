<?php 
// src/Controller/ScheduleController.php
namespace App\Controller;

use App\Repository\StudentRepository;
use App\Repository\CourseSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ScheduleController extends AbstractController
{
    #[Route('/api/students/{id}/schedule/next-day', name: 'student.schedule_next_day', methods: ['GET'])]
    public function nextDay(
        int $id,
        StudentRepository $students,
        CourseSessionRepository $sessionsRepo
    ): JsonResponse {
        $student = $students->find($id);
        if (!$student) return new JsonResponse(['message' => 'Student not found'], 404);
        $classe = $student->getClasse();
        if (!$classe) return new JsonResponse([], 200);

        $tz = new \DateTimeZone('Europe/Paris');
        $from = (new \DateTimeImmutable('tomorrow', $tz))->setTime(0,0,0);
        $to   = $from->modify('+1 day');

        $sessions = $sessionsRepo->findByClasseBetween($classe, $from, $to);

        $payload = array_map(static function ($s) {
            $course = $s->getCourse();
            $prof   = method_exists($s, 'getProfessor') ? $s->getProfessor() : null;
            return [
                'title' => $course?->getName() ?? 'Cours',
                'start' => $s->getStartAt()?->format(\DATE_ATOM),
                'end'   => $s->getEndAt()?->format(\DATE_ATOM),
                'extendedProps' => [
                    'professor' => $prof?->getLastname() ?? $prof?->getName() ?? null,
                    'location'  => $s->getRoom() ?? null,
                ],
            ];
        }, $sessions);

        return new JsonResponse($payload, 200);
    }
}