<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Student;
use App\Entity\Category;
use App\Entity\Professor;
use App\Entity\CourseSession;
use App\Entity\Absence;
use App\Entity\Grade;
use App\Repository\CourseSessionRepository;
use App\Repository\ProfessorRepository;
use App\Repository\UserRepository;
use App\Repository\StudentRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoints pour les professeurs (séances, appel, notes)
 */
#[Route('/api/prof')]
#[IsGranted('ROLE_PROFESSOR')]
class ProfessorSessionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Liste les séances du professeur connecté, avec filtre date facultatif.
     * GET /api/prof/sessions?from=2025-08-25&to=2025-08-31
     */
    #[Route('/sessions', name: 'prof_sessions_list', methods: ['GET'])]
    public function listSessions(
        Request $request,
        CourseSessionRepository $sessionRepo,
        ProfessorRepository $profRepo
    ): JsonResponse {
        /** @var User $me */
        $me = $this->getUser();
        if (!$me) return $this->json(['error' => 'Unauthenticated'], 401);

        /** @var Professor|null $prof */
        $prof = $profRepo->findOneBy(['userId' => $me]);
        if (!$prof) return $this->json(['error' => 'Current user is not a professor'], 403);

        $from = $request->query->get('from');
        $to   = $request->query->get('to');

        $qb = $sessionRepo->createQueryBuilder('s')
            ->andWhere('s.professor = :p')->setParameter('p', $prof)
            ->orderBy('s.startAt', 'ASC');

        if ($from) {
            $fromDate = new \DateTimeImmutable($from . ' 00:00:00');
            $qb->andWhere('s.startAt >= :from')->setParameter('from', $fromDate);
        }
        if ($to) {
            $toDate = new \DateTimeImmutable($to . ' 23:59:59');
            $qb->andWhere('s.startAt <= :to')->setParameter('to', $toDate);
        }

        $sessions = $qb->getQuery()->getResult();

        $data = array_map(function (CourseSession $s) {
            // Vérifier s'il y a des absences enregistrées pour cette séance
            $hasRoll = $this->em->getRepository(Absence::class)
                ->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.courseSession = :session')
                ->setParameter('session', $s)
                ->getQuery()
                ->getSingleScalarResult() > 0;

            return [
                'id'       => $s->getId(),
                'course'   => method_exists($s->getCourse(), 'getName') ? $s->getCourse()->getName() : $s->getCourse()->getId(),
                'classe'   => method_exists($s->getClasse(), 'getName') ? $s->getClasse()->getName() : ($s->getClasse()?->getId()),
                'startAt'  => $s->getStartAt()?->format(\DateTimeInterface::ATOM),
                'endAt'    => $s->getEndAt()?->format(\DateTimeInterface::ATOM),
                'room'     => method_exists($s, 'getRoom') ? $s->getRoom() : null,
                'hasRoll'  => $hasRoll,
            ];
        }, $sessions);

        return $this->json($data);
    }

    /**
     * Récupère la liste des élèves (roster) d'une séance
     * GET /api/prof/sessions/{id}/roster
     */
    #[Route('/sessions/{id}/roster', name: 'prof_session_roster', methods: ['GET'])]
    public function getRoster(
        CourseSession $session,
        ProfessorRepository $profRepo,
        \Doctrine\ORM\EntityManagerInterface $em
    ): JsonResponse {
        $this->denyUnlessOwnedByCurrentProfessor($session, $profRepo);

        $classe = $session->getClasse();
        if (!$classe) {
            return $this->json([
                'sessionId' => $session->getId(),
                'classeId'  => null,
                'studentCount' => 0,
                'students' => [],
                'debug' => 'La séance n’est liée à aucune classe.'
            ]);
        }

        $studentMeta = $em->getClassMetadata(\App\Entity\Student::class);

        // 1) Cherche une association ManyToOne Student -> Classe (nom inconnu)
        $toClasseField = null;
        foreach ($studentMeta->associationMappings as $field => $map) {
            if ($map['targetEntity'] === \App\Entity\Classe::class && $map['type'] === \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE) {
                $toClasseField = $field;
                break;
            }
        }

        // 2) Sinon, cherche une association ManyToMany (Student -> classes)
        $manyToManyField = null;
        if (!$toClasseField) {
            foreach ($studentMeta->associationMappings as $field => $map) {
                if ($map['targetEntity'] === \App\Entity\Classe::class && $map['type'] === \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
                    $manyToManyField = $field;
                    break;
                }
            }
        }

        $qb = $em->getRepository(\App\Entity\Student::class)->createQueryBuilder('st')
            // essaie de rejoindre l'utilisateur si la relation existe
            ->leftJoin('st.user', 'u')->addSelect('u')
            ->orderBy('u.lastname', 'ASC');

        if ($toClasseField) {
            // cas ManyToOne : st.<field> = :classe
            $qb->andWhere(sprintf('st.%s = :classe', $toClasseField))
            ->setParameter('classe', $classe);
        } elseif ($manyToManyField) {
            // cas ManyToMany : JOIN st.<field> c WHERE c = :classe
            $qb->join(sprintf('st.%s', $manyToManyField), 'c')
            ->andWhere('c = :classe')
            ->setParameter('classe', $classe);
        } else {
            // pas d’association détectée -> explique clairement
            return $this->json([
                'sessionId' => $session->getId(),
                'classeId'  => $classe->getId(),
                'studentCount' => 0,
                'students' => [],
                'debug' => 'Aucune association Student -> Classe (ManyToOne ou ManyToMany) détectée. Vérifie l’entité Student.'
            ]);
        }

        $students = $qb->getQuery()->getResult();

        $out = [];
        foreach ($students as $st) {
            $u = method_exists($st, 'getUser') ? $st->getUser() : null;
            $out[] = [
                'studentId' => $st->getId(),
                'userId'    => $u?->getId(),
                'name'      => $u?->getName(),
                'lastname'  => $u?->getLastname(),
                'email'     => $u?->getEmail(),
            ];
        }

        return $this->json([
            'sessionId'    => $session->getId(),
            'classeId'     => $classe->getId(),
            'studentCount' => count($out),
            'students'     => $out
        ]);
    }
    
    private function findAssociationField(string $ownerClass, string $targetClass): ?string
    {
        $meta = $this->em->getClassMetadata($ownerClass);
        foreach ($meta->associationMappings as $field => $map) {
            if ($map['targetEntity'] === $targetClass) {
                return $field; // ex: "session" ou "course"
            }
        }
        return null;
    }

    private function setAssociationValue(object $entity, string $field, mixed $value): void
    {
        $setter = 'set' . ucfirst($field);
        if (method_exists($entity, $setter)) {
            $entity->$setter($value);
            return;
        }
        // fallback courants si le nom n’est pas standard
        foreach (['setCourseSession','setSession','setCourse'] as $alt) {
            if (method_exists($entity, $alt)) { $entity->$alt($value); return; }
        }
    }

    /**
     * Enregistre l'appel (présence/absence/retard) pour une séance.
     * POST /api/prof/sessions/{id}/roll
     * Body JSON:
     * {
     *   "attendances": [
     *     {"studentId": 10, "userId":null, "status":"ABSENT","minutesLate":0,"justified":false,"note":null}
     *   ]
     * }
     */
    #[Route('/sessions/{id}/roll', name: 'prof_session_roll', methods: ['POST'])]
    public function saveRoll(
        CourseSession $session,
        Request $request,
        ProfessorRepository $profRepo,
        StudentRepository $studentRepo,
        UserRepository $userRepo
    ): JsonResponse {
        // 1) sécurité
        $this->denyUnlessOwnedByCurrentProfessor($session, $profRepo);

        $data = $request->toArray();
        $rows = $data['attendances'] ?? [];
        $processed = 0;

        // 2) bornes de la séance -> converties en \DateTime (mutable)
        $startAt = method_exists($session, 'getStartAt') ? $session->getStartAt() : null;
        $endAt   = method_exists($session, 'getEndAt')   ? $session->getEndAt()   : null;

        if ($startAt instanceof \DateTimeImmutable) { $startAt = \DateTime::createFromImmutable($startAt); }
        if ($endAt   instanceof \DateTimeImmutable) { $endAt   = \DateTime::createFromImmutable($endAt); }

        if ($startAt === null) { $startAt = new \DateTime(); }
        if ($endAt   === null) { $endAt   = (clone $startAt)->modify('+120 minutes'); }

        // 3) semestre (si dispo)
        $course   = method_exists($session, 'getCourse') ? $session->getCourse() : null;
        $semester = ($course && method_exists($course, 'getSemester')) ? $course->getSemester() : null;

        foreach ($rows as $row) {
            // --- résolution Student/User ---
            $student = null; $user = null;
            $studentId = $row['studentId'] ?? null;
            $userId    = $row['userId'] ?? null;

            if ($studentId) {
                $student = $studentRepo->find((int)$studentId);
            }
            if (!$student && $userId) {
                $student = $studentRepo->findOneBy(['user' => (int)$userId]);
                if (!$student) $user = $userRepo->find((int)$userId);
            }
            if (!$student && !$user) {
                return $this->json(['error' => 'Student not found'], 400);
            }

            // --- champs métier ---
            $status      = strtoupper((string)($row['status'] ?? 'PRESENT'));
            $minutesLate = isset($row['minutesLate']) ? (int)$row['minutesLate'] : 0;
            $justified   = (bool)($row['justified'] ?? false);
            $note        = $row['note'] ?? null;

            // --- upsert par (courseSession, student) ---
            $criteria = ['courseSession' => $session];
            if ($this->absenceUsesStudent()) {
                $criteria['student'] = $student;
            } else {
                $criteria['student'] = $user ?: ($student?->getUser());
            }

            /** @var Absence|null $absence */
            $absence = $this->em->getRepository(Absence::class)->findOneBy($criteria);
            if (!$absence) {
                $absence = new Absence();
                $absence->setCourseSession($session);
                if ($this->absenceUsesStudent()) {
                    $absence->setStudent($student);
                } else {
                    $absence->setStudent($criteria['student']); // User
                }
                if (method_exists($absence, 'setCreatedAt')) {
                    $absence->setCreatedAt(new \DateTimeImmutable());
                }
            }

            // --- dates (tes colonnes sont DATETIME_MUTABLE) ---
            if (method_exists($absence, 'setStartedDate')) $absence->setStartedDate(clone $startAt);
            if (method_exists($absence, 'setEndedDate'))   $absence->setEndedDate(clone $endAt);

            // --- autres champs ---
            if ($semester && method_exists($absence, 'setSemester')) $absence->setSemester($semester);
            if (method_exists($absence, 'setStatus'))            $absence->setStatus($status);
            if (method_exists($absence, 'setMinutesLate'))       $absence->setMinutesLate($minutesLate);
            if (method_exists($absence, 'setJustified'))         $absence->setJustified($justified);
            if (method_exists($absence, 'setJustificationNote')) $absence->setJustificationNote($note);
            if (method_exists($absence, 'setRecordedBy'))        $absence->setRecordedBy($this->getUser());

            $this->em->persist($absence);
            $processed++;
        }

        $this->em->flush();
        return $this->json(['ok' => true, 'processed' => $processed]);
    }


    #[Route('/sessions/with-students', name: 'prof_sessions_with_students', methods: ['GET'])]
    public function listSessionsWithStudentCount(
        CourseSessionRepository $sessionRepo,
        ProfessorRepository $profRepo,
        \App\Repository\StudentRepository $studentRepo
    ): JsonResponse {
        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $prof = $profRepo->findOneBy(['userId' => $me]);

        // Récupère TOUTES les séances du prof
        $sessions = $sessionRepo->createQueryBuilder('s')
            ->andWhere('s.professor = :p')->setParameter('p', $prof)
            ->leftJoin('s.classe', 'c')->addSelect('c')
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()->getResult();

        // Compte les étudiants par classe via repo Student (robuste)
        $out = [];
        foreach ($sessions as $s) {
            $classe = $s->getClasse();
            $count = 0;
            if ($classe) {
                $count = (int) $studentRepo->createQueryBuilder('st')
                    ->select('COUNT(st.id)')
                    ->andWhere('st.classe = :classe')->setParameter('classe', $classe)
                    ->getQuery()->getSingleScalarResult();
            }
            $out[] = [
                'sessionId' => $s->getId(),
                'classeId'  => $classe?->getId(),
                'studentCount' => $count,
                'startAt'   => $s->getStartAt()?->format(DATE_ATOM),
                'course'    => method_exists($s->getCourse(), 'getName') ? $s->getCourse()->getName() : $s->getCourse()->getId(),
            ];
        }
        return $this->json($out);
    }


    /**
     * Enregistre des notes pour une séance.
     * POST /api/prof/sessions/{id}/grades
     * Body JSON:
     * {
     *   "grades": [
     *     {"studentId":10,"userId":null,"score":14.5,"outOf":20,"coefficient":1,"categoryId":null,"comment":""}
     *   ]
     * }
     */
    #[Route('/sessions/{id}/grades', name: 'prof_session_grades', methods: ['POST'])]
    public function saveGrades(
        CourseSession $session,
        Request $request,
        ProfessorRepository $profRepo,
        StudentRepository $studentRepo,
        UserRepository $userRepo,
        CategoryRepository $categoryRepo
    ): JsonResponse {
        $this->denyUnlessOwnedByCurrentProfessor($session, $profRepo);

        $payload = $request->toArray();
        $items = $payload['grades'] ?? [];

        foreach ($items as $row) {
            $student   = null;
            $studentId = $row['studentId'] ?? null;
            $userId    = $row['userId'] ?? null;

            if ($studentId) {
                $student = $studentRepo->find($studentId);
            } elseif ($userId) {
                $student = $studentRepo->findOneBy(['user' => $userId]);
            }

            $user = null;
            if (!$student && $userId) {
                $user = $userRepo->find($userId);
            } elseif ($student && method_exists($student, 'getUser')) {
                $user = $student->getUser();
            }

            $score       = (float)($row['score'] ?? 0);
            $outOf       = isset($row['outOf']) ? (float)$row['outOf'] : 20.0;
            $coefficient = isset($row['coefficient']) ? (float)$row['coefficient'] : 1.0;
            $comment     = $row['comment'] ?? null;
            $category    = null;

            if (!empty($row['categoryId'])) {
                $category = $categoryRepo->find((int)$row['categoryId']);
            }

            // Upsert Grade (unique par student/session/(category))
            $criteria = ['courseSession' => $session];
            if ($this->gradeUsesStudent()) {
                $criteria['student'] = $student;
            } else {
                $criteria['student'] = $user; // ici "student" pointe vers User
            }
            if ($category) $criteria['category'] = $category;

            $grade = $this->em->getRepository(Grade::class)->findOneBy($criteria);
            if (!$grade) $grade = new Grade();

            if (method_exists($grade, 'setCourseSession')) $grade->setCourseSession($session);
            if ($this->gradeUsesStudent()) {
                if (method_exists($grade, 'setStudent')) $grade->setStudent($student);
            } else {
                if (method_exists($grade, 'setStudent')) $grade->setStudent($user);
            }
            if (method_exists($grade, 'setCategory')) $grade->setCategory($category);

            if (method_exists($grade, 'setScore'))       $grade->setScore((string)$score);
            if (method_exists($grade, 'setOutOf'))       $grade->setOutOf((string)$outOf);
            if (method_exists($grade, 'setCoefficient')) $grade->setCoefficient((string)$coefficient);
            if (method_exists($grade, 'setComment'))     $grade->setComment($comment);
            if (method_exists($grade, 'setRecordedBy'))  $grade->setRecordedBy($this->getUser());
            if (method_exists($grade, 'setCreatedAt') && !$grade->getId()) $grade->setCreatedAt(new \DateTimeImmutable());
            if (method_exists($grade, 'setUpdatedAt'))   $grade->setUpdatedAt(new \DateTimeImmutable());

            $this->em->persist($grade);
        }

        $this->em->flush();
        return $this->json(['ok' => true]);
    }

    /**
     * Vérifie que la séance appartient au professeur (user courant).
     * Lève une 403 sinon.
     */
    private function denyUnlessOwnedByCurrentProfessor(CourseSession $session, ProfessorRepository $profRepo): void
    {
        /** @var User $me */
        $me = $this->getUser();
        if (!$me) {
            throw $this->createAccessDeniedException('Unauthenticated');
        }

        $sessionProfessor = $session->getProfessor(); // Professor
        $ownerUser = method_exists($sessionProfessor, 'getUser')
            ? $sessionProfessor->getUser()
            : (method_exists($sessionProfessor, 'getUserId') ? $sessionProfessor->getUserId() : null);

        if (!$ownerUser || $ownerUser->getId() !== $me->getId()) {
            // Admin bypass (si besoin)
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('You cannot manage this session.');
            }
        }
    }

    /** Détecte si Absence::student pointe vers Student (true) ou User (false) */
    private function absenceUsesStudent(): bool
    {
        $meta = $this->em->getClassMetadata(Absence::class);
        return isset($meta->associationMappings['student'])
            && $meta->associationMappings['student']['targetEntity'] === Student::class;
    }

    /** Détecte si Grade::student pointe vers Student (true) ou User (false) */
    private function gradeUsesStudent(): bool
    {
        $meta = $this->em->getClassMetadata(Grade::class);
        return isset($meta->associationMappings['student'])
            && $meta->associationMappings['student']['targetEntity'] === Student::class;
    }
}
