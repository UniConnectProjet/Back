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
     * Récupère les cours du professeur connecté
     * GET /api/prof/courses
     */
    #[Route('/courses', name: 'prof_courses_list', methods: ['GET'])]
    public function getMyCourses(
        ProfessorRepository $profRepo
    ): JsonResponse {
        /** @var User $me */
        $me = $this->getUser();
        if (!$me) return $this->json(['error' => 'Unauthenticated'], 401);

        /** @var Professor|null $prof */
        $prof = $profRepo->findOneBy(['userId' => $me]);
        if (!$prof) return $this->json(['error' => 'Current user is not a professor'], 403);

        $courses = $prof->getCourses();
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'average' => $course->getAverage(),
                'courseUnit' => $course->getCourseUnit() ? $course->getCourseUnit()->getName() : null,
            ];
        }

        return $this->json($data);
    }



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
     * Récupère les absences enregistrées pour une séance.
     * GET /api/prof/sessions/{id}/roll
     */
    #[Route('/sessions/{id}/roll', name: 'prof_session_get_roll', methods: ['GET'], priority: 1)]
    public function getRoll(
        CourseSession $session,
        ProfessorRepository $profRepo
    ): JsonResponse {
        try {
            // Debug: vérifier l'utilisateur connecté
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Non authentifié'], 401);
            }

            // Debug: vérifier le professeur
            $prof = $profRepo->findOneBy(['userId' => $user]);
            if (!$prof) {
                return $this->json(['error' => 'Utilisateur non professeur'], 403);
            }

            // Debug: vérifier la propriété de la séance
            $sessionProfessor = $session->getProfessor();
            if (!$sessionProfessor || $sessionProfessor->getId() !== $prof->getId()) {
                return $this->json(['error' => 'Séance non accessible'], 403);
            }

            // Récupérer tous les étudiants de la classe de cette session
            $students = $this->em->getRepository(Student::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.user', 'u')
                ->leftJoin('s.classe', 'c')
                ->where('c.id = :classeId')
                ->setParameter('classeId', $session->getClasse()->getId())
                ->getQuery()
                ->getResult();

            // Récupérer les absences existantes pour cette session
            $absences = $this->em->getRepository(Absence::class)
                ->createQueryBuilder('a')
                ->leftJoin('a.student', 's')
                ->where('a.courseSession = :session')
                ->setParameter('session', $session)
                ->getQuery()
                ->getResult();

            // Créer un map des absences par studentId pour un accès rapide
            $absenceMap = [];
            foreach ($absences as $absence) {
                $studentId = $absence->getStudent()->getId();
                $absenceMap[$studentId] = $absence;
            }

            // Construire la réponse avec tous les étudiants
            $data = array_map(function (Student $student) use ($absenceMap) {
                $user = $student->getUser();
                $absence = $absenceMap[$student->getId()] ?? null;
                
                if ($absence) {
                    // L'étudiant a une absence enregistrée
                    $presenceStatus = method_exists($absence, 'getPresenceStatus') ? $absence->getPresenceStatus() : 'PRESENT';
                    $minutesLate = method_exists($absence, 'getMinutesLate') ? $absence->getMinutesLate() : 0;
                    
                    // Si presenceStatus n'est pas défini, calculer basé sur les anciens champs
                    if (!$presenceStatus) {
                        if (!$absence->isJustified() || $absence->getStatus() === Absence::STATUS_UNJUSTIFIED) {
                            $presenceStatus = $minutesLate > 0 ? 'LATE' : 'ABSENT';
                } else {
                            $presenceStatus = $minutesLate > 0 ? 'LATE' : 'PRESENT';
                        }
                    }
                    
                    return [
                        'studentId' => $student->getId(),
                        'userId' => $user?->getId(),
                        'status' => $presenceStatus,
                        'minutesLate' => $minutesLate,
                        'justified' => $absence->isJustified() ?? false,
                        'justificationStatus' => $absence->getStatus(),
                        'note' => method_exists($absence, 'getJustificationNote') ? $absence->getJustificationNote() : null,
                    ];
                } else {
                    // L'étudiant n'a pas d'absence enregistrée = présent
                    return [
                        'studentId' => $student->getId(),
                        'userId' => $user?->getId(),
                        'status' => 'PRESENT',
                        'minutesLate' => 0,
                        'justified' => true,
                        'justificationStatus' => null,
                        'note' => null,
                    ];
                }
            }, $students);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des absences',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
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

    #[Route('/classes', name: 'prof_classes_list', methods: ['GET'])]
    public function getMyClasses(): JsonResponse
    {
        // TEMPORAIRE: Test sans authentification
        $userRepo = $this->em->getRepository(\App\Entity\User::class);
        $user = $userRepo->findOneBy(['email' => 'prof01@example.com']);
        if (!$user) return $this->json(['error' => 'Test user not found'], 404);

        $profRepo = $this->em->getRepository(\App\Entity\Professor::class);
        $prof = $profRepo->findOneBy(['userId' => $user]);
        if (!$prof) return $this->json(['error' => 'Test professor not found'], 404);

        // Récupérer les classes du professeur via ses cours (requête optimisée)
        $classesData = $this->em->getRepository(\App\Entity\Classe::class)
            ->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->innerJoin('c.courses', 'course')
            ->innerJoin('course.professors', 'prof')
            ->where('prof = :prof')
            ->setParameter('prof', $prof)
            ->groupBy('c.id, c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($classesData as $classe) {
            // Pour chaque classe, récupérer les cours associés au professeur
            $courses = $this->em->getRepository(\App\Entity\Course::class)
                ->createQueryBuilder('course')
                ->select('course.name')
                ->innerJoin('course.classes', 'classe')
                ->innerJoin('course.professors', 'prof')
                ->where('classe.id = :classId')
                ->andWhere('prof = :prof')
                ->setParameter('classId', $classe['id'])
                ->setParameter('prof', $prof)
                ->getQuery()
                ->getResult();

            $data[] = [
                'id' => $classe['id'],
                'name' => $classe['name'],
                'courses' => array_map(fn($c) => $c['name'], $courses)
            ];
        }

        return $this->json($data);
    }

    #[Route('/classes/{classId}/students', name: 'prof_class_students', methods: ['GET'])]
    public function getClassStudents(int $classId): JsonResponse
    {
        // Récupérer les étudiants de la classe
        $students = $this->em->getRepository(\App\Entity\Student::class)
            ->createQueryBuilder('s')
            ->select('s.id as studentId, u.lastname, u.name, u.email')
            ->innerJoin('s.classe', 'c')
            ->innerJoin('s.user', 'u')
            ->where('c.id = :classId')
            ->setParameter('classId', $classId)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'students' => $students
        ]);
    }

    #[Route('/classes/{classId}/courses', name: 'prof_class_courses', methods: ['GET'])]
    public function getClassCourses(int $classId): JsonResponse
    {
        // Trouver un professeur de test
        $userRepo = $this->em->getRepository(\App\Entity\User::class);
        $user = $userRepo->findOneBy(['email' => 'prof01@example.com']);

        if (!$user) {
            return $this->json(['error' => 'Test user not found'], 404);
        }

        $profRepo = $this->em->getRepository(\App\Entity\Professor::class);
        $prof = $profRepo->findOneBy(['userId' => $user]);
        if (!$prof) {
            return $this->json(['error' => 'Test professor not found'], 404);
        }

        // Récupérer les cours du professeur pour cette classe
        $courses = $this->em->getRepository(\App\Entity\Course::class)
            ->createQueryBuilder('course')
            ->select('course.id, course.name')
            ->innerJoin('course.classes', 'classe')
            ->innerJoin('course.professors', 'prof')
            ->where('classe.id = :classId')
            ->andWhere('prof = :prof')
            ->setParameter('classId', $classId)
            ->setParameter('prof', $prof)
            ->getQuery()
            ->getResult();

        return $this->json([
            'courses' => $courses
        ]);
    }

    #[Route('/roll/save', name: 'prof_save_roll', methods: ['POST'])]
    public function saveRoll(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['sessionId']) || !isset($data['attendances'])) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $sessionId = $data['sessionId'];
        $attendances = $data['attendances'];

        try {
            // Récupérer la session
            $session = $this->em->getRepository(\App\Entity\CourseSession::class)->find($sessionId);
            if (!$session) {
                return $this->json(['error' => 'Session non trouvée'], 404);
            }

            // Déterminer le semestre en fonction de la date de la session
            $sessionDate = $session->getStartAt();
            $semester = $this->em->getRepository(\App\Entity\Semester::class)
                ->createQueryBuilder('s')
                ->where('s.startDate <= :date')
                ->andWhere('s.endDate >= :date')
                ->setParameter('date', $sessionDate)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$semester) {
                return $this->json(['error' => 'Aucun semestre trouvé pour cette date'], 404);
            }

            // Récupérer tous les étudiants de la classe de cette session pour validation
            $classStudents = $this->em->getRepository(\App\Entity\Student::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.classe', 'c')
                ->where('c.id = :classeId')
                ->setParameter('classeId', $session->getClasse()->getId())
                ->getQuery()
                ->getResult();

            $validStudentIds = array_map(fn($s) => $s->getId(), $classStudents);

            $savedAbsences = [];

            // Parcourir les présences
            foreach ($attendances as $studentId => $attendance) {
                // Valider que l'étudiant appartient à la classe de la session
                if (!in_array($studentId, $validStudentIds)) {
                    return $this->json([
                        'error' => "L'étudiant ID $studentId n'appartient pas à la classe de cette session",
                        'validStudentIds' => $validStudentIds
                    ], 400);
                }

                $student = $this->em->getRepository(\App\Entity\Student::class)->find($studentId);
                if (!$student) continue;

                $presenceStatus = $attendance['status'] ?? 'PRESENT';
                $minutesLate = $attendance['minutesLate'] ?? 0;
                $justificationNote = $attendance['justificationNote'] ?? '';

                // Si présent, pas besoin de créer d'absence
                if ($presenceStatus === 'PRESENT') {
                    continue;
                }

                // Vérifier si une absence existe déjà pour cette session et cet étudiant
                $existingAbsence = $this->em->getRepository(\App\Entity\Absence::class)
                    ->findOneBy([
                        'student' => $student,
                        'courseSession' => $session
                    ]);

                if ($existingAbsence) {
                    // Mettre à jour l'absence existante
                    $existingAbsence->setPresenceStatus($presenceStatus);
                    $existingAbsence->setMinutesLate($minutesLate);
                    $existingAbsence->setJustificationNote($justificationNote);
                    $existingAbsence->setJustified($presenceStatus === 'LATE' && $attendance['justified'] ?? false);
                } else {
                    // Créer une nouvelle absence
                    $absence = new \App\Entity\Absence();
                    $absence->setStudent($student);
                    $absence->setCourseSession($session);
                    $absence->setSemester($semester);
                    // Convertir DateTimeImmutable en DateTime pour Doctrine
                    $startDate = $session->getStartAt();
                    $endDate = $session->getEndAt();
                    
                    if ($startDate instanceof \DateTimeImmutable) {
                        $startDate = \DateTime::createFromImmutable($startDate);
                    }
                    if ($endDate instanceof \DateTimeImmutable) {
                        $endDate = \DateTime::createFromImmutable($endDate);
                    }
                    
                    $absence->setStartedDate($startDate);
                    $absence->setEndedDate($endDate);
                    $absence->setPresenceStatus($presenceStatus);
                    $absence->setMinutesLate($minutesLate);
                    $absence->setJustificationNote($justificationNote);
                    $absence->setJustified($presenceStatus === 'LATE' && $attendance['justified'] ?? false);
                    $absence->setStatus(\App\Entity\Absence::STATUS_UNJUSTIFIED);

                    $this->em->persist($absence);
                }

                $savedAbsences[] = [
                    'studentId' => $studentId,
                    'status' => $presenceStatus,
                    'minutesLate' => $minutesLate,
                    'justified' => $attendance['justified'] ?? false
                ];
            }

            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Présences enregistrées avec succès',
                'savedAbsences' => $savedAbsences,
                'semester' => [
                    'id' => $semester->getId(),
                    'name' => $semester->getName()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ], 500);
        }
    }
}
