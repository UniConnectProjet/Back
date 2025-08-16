<?php 
namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\StudentClasseRepository;
use App\Repository\CourseSessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ScheduleController extends AbstractController
{
    #[Route('/api/students/{id}/schedule/next-day', name: 'schedule.next_day', methods: ['GET'])]
    public function nextDay(
        int $id,
        UserRepository $users,
        StudentClasseRepository $studentClasseRepo,
        CourseSessionRepository $sessionsRepo
    ): JsonResponse {
        $student = $users->find($id);
        if (!$student) return new JsonResponse(['error' => 'Unknown student'], 404);

        // récupère les classes de l’étudiant (table student_classe)
        $links = $studentClasseRepo->findBy(['user' => $student]);
        $classes = array_map(fn($l) => $l->getClasse(), $links);
        if (!$classes) return new JsonResponse([], 200);

        $from = (new \DateTimeImmutable('tomorrow'))->setTime(0, 0);
        $to   = $from->modify('+1 day');

        $sessions = $sessionsRepo->findForClassesBetween($classes, $from, $to);
        $events = array_map(function($s) {
            $prof = $s->getProfessor();
            return [
                'title' => $s->getCourse()->getName(),
                'start' => $s->getStartAt()->format(\DATE_ATOM),
                'end'   => $s->getEndAt()->format(\DATE_ATOM),
                'extendedProps' => [
                    'professor' => $prof ? ($prof->getFirstname().' '.$prof->getLastname()) : null,
                    'location'  => $s->getRoom(),
                ],
            ];
        }, $sessions);

        return new JsonResponse($events, 200);
    }

    #[Route('/api/students/{id}/schedule', name: 'schedule.range', methods: ['GET'])]
    public function byRange(
        int $id,
        Request $request,
        UserRepository $users,
        StudentClasseRepository $studentClasseRepo,
        CourseSessionRepository $sessionsRepo
    ): JsonResponse {
        $student = $users->find($id);
        if (!$student) return new JsonResponse(['error' => 'Unknown student'], 404);

        $links = $studentClasseRepo->findBy(['user' => $student]);
        $classes = array_map(fn($l) => $l->getClasse(), $links);
        if (!$classes) return new JsonResponse([], 200);

        
        $from = $request->query->get('from') ? new \DateTimeImmutable($request->query->get('from')) : new \DateTimeImmutable('monday this week');
        $to   = $request->query->get('to')   ? new \DateTimeImmutable($request->query->get('to'))   : new \DateTimeImmutable('sunday this week 23:59');

        $sessions = $sessionsRepo->findForClassesBetween($classes, $from, $to);

        $events = array_map(function($s) {
            $prof = $s->getProfessor();
            return [
                'title' => $s->getCourse()->getName(),
                'start' => $s->getStartAt()->format(\DATE_ATOM),
                'end'   => $s->getEndAt()->format(\DATE_ATOM),
                'extendedProps' => [
                    'professor' => $prof ? ($prof->getFirstname().' '.$prof->getLastname()) : null,
                    'location'  => $s->getRoom(),
                ],
            ];
        }, $sessions);

        return new JsonResponse($events, 200);
    }
}