<?php 
namespace App\Controller;

use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/api/students/{id}/schedule', name: 'student.schedule_range', methods: ['GET'])]
    public function range(
        int $id,
        Request $request,
        StudentRepository $students,
        CourseSessionRepository $sessionsRepo
    ): JsonResponse {
        $student = $students->find($id);
        if (!$student) {
            return new JsonResponse(['message' => 'Student not found'], 404);
        }

        $classe = method_exists($student, 'getClasse') ? $student->getClasse() : null;
        if (!$classe) {
            return new JsonResponse([], 200);
        }

        $tz = new \DateTimeZone('Europe/Paris');

        $fromParam = $request->query->get('from');
        $toParam   = $request->query->get('to');

        if ($fromParam && $toParam) {
            $from = new \DateTime($fromParam, $tz);
            $to   = new \DateTime($toParam, $tz);
        } else {
            $from = new \DateTime('monday this week 00:00:00', $tz);
            $to   = (clone $from)->modify('+7 days');
        }

        if (method_exists($sessionsRepo, 'findByClasseAndPeriod')) {
            $sessions = $sessionsRepo->findByClasseAndPeriod($classe, $from, $to);
        } else {
            $qb = $sessionsRepo->createQueryBuilder('s')
                ->andWhere('s.classe = :classe')
                ->andWhere('s.startAt < :to')
                ->andWhere('s.endAt > :from')
                ->setParameter('classe', $classe)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->orderBy('s.startAt', 'ASC');

            $sessions = $qb->getQuery()->getResult();
        }

        $payload = array_map(static function ($s) {
            $course = method_exists($s, 'getCourse') ? $s->getCourse() : null;
            $prof   = method_exists($s, 'getProfessor') ? $s->getProfessor() : null;

            return [
                'title' => $course?->getName() ?? 'Cours',
                'start' => $s->getStartAt()?->format(\DATE_ATOM),
                'end'   => $s->getEndAt()?->format(\DATE_ATOM),
                'extendedProps' => [
                    'professor' => $prof?->getLastname() ?? $prof?->getName() ?? null,
                    'location'  => method_exists($s, 'getRoom') ? $s->getRoom() : null,
                ],
            ];
        }, $sessions);

        return new JsonResponse($payload, 200);
    }

}