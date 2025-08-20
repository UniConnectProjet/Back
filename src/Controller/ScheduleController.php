<?php 
namespace App\Controller;

use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CourseSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        // On prend la classe de l'étudiant
        $classe = method_exists($student, 'getClasse') ? $student->getClasse() : null;
        if (!$classe) {
            return $this->json([], Response::HTTP_OK);
        }

        // Fenêtre: demain 00:00 → demain 23:59:59
        $start = (new \DateTimeImmutable('tomorrow'))->setTime(0,0,0);
        $end   = (new \DateTimeImmutable('tomorrow'))->setTime(23,59,59);

        // Requête explicite
        $sessions = $sessionsRepo->createQueryBuilder('s')
            ->andWhere('s.classe = :classe')
            ->andWhere('s.startAt >= :start AND s.startAt <= :end')
            ->setParameter('classe', $classe)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Mapping minimal (gardé proche de ton code)
        $payload = array_map(function ($s) {
            $course = method_exists($s, 'getCourse') ? $s->getCourse() : null;
            $prof = method_exists($course, 'getProfessor') ? $course->getProfessor() : null;

            return [
                'title' => $course?->getName() ?? 'Cours',
                'start' => method_exists($s, 'getStartAt') ? $s->getStartAt()?->format(\DATE_ATOM) : null,
                'end'   => method_exists($s, 'getEndAt')   ? $s->getEndAt()?->format(\DATE_ATOM)   : null,
                'extendedProps' => [
                    'professor' => $prof?->getLastname() ?? $prof?->getName() ?? null,
                    'location'  => method_exists($s, 'getRoom') ? $s->getRoom() : null,
                ],
            ];
        }, $sessions);

        return $this->json($payload, Response::HTTP_OK);
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
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        $from = $request->query->get('from');
        $to   = $request->query->get('to');
        if (!$from || !$to) {
            return $this->json(['message' => 'Missing dates'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDt = (new \DateTimeImmutable($from))->setTime(0,0,0);
            $toDt   = (new \DateTimeImmutable($to))->setTime(23,59,59);
        } catch (\Exception) {
            return $this->json(['message' => 'Invalid dates'], Response::HTTP_BAD_REQUEST);
        }

        if ($fromDt > $toDt) {
            return $this->json(['message' => 'Invalid range'], Response::HTTP_BAD_REQUEST);
        }

        $classe = method_exists($student, 'getClasse') ? $student->getClasse() : null;
        if (!$classe) {
            return $this->json([], Response::HTTP_OK);
        }

        $sessions = $sessionsRepo->createQueryBuilder('s')
            ->andWhere('s.classe = :classe')
            ->andWhere('s.startAt >= :from AND s.startAt <= :to')
            ->setParameter('classe', $classe)
            ->setParameter('from', $fromDt)
            ->setParameter('to', $toDt)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $payload = array_map(function ($s) {
            $course = method_exists($s, 'getCourse') ? $s->getCourse() : null;
            $prof = method_exists($course, 'getProfessor') ? $course->getProfessor() : null;

            return [
                'title' => $course?->getName() ?? 'Cours',
                'start' => method_exists($s, 'getStartAt') ? $s->getStartAt()?->format(\DATE_ATOM) : null,
                'end'   => method_exists($s, 'getEndAt')   ? $s->getEndAt()?->format(\DATE_ATOM)   : null,
                'extendedProps' => [
                    'professor' => $prof?->getLastname() ?? $prof?->getName() ?? null,
                    'location'  => method_exists($s, 'getRoom') ? $s->getRoom() : null,
                ],
            ];
        }, $sessions);

        return $this->json($payload, Response::HTTP_OK);
    }

}