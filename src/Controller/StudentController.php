<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Student;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\StudentRepository;
use App\Repository\ClasseRepository;
use App\Repository\GradeRepository;
use App\Repository\UserRepository;
use App\Repository\SemesterRepository;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpFoundation\Request;

#[Route('/api')]
class StudentController extends AbstractController
{
    private const ROUTE_FOR_A_STUDENT = '/api/students/{id}';
    private StudentRepository $repository;
    private SerializerInterface $serializer;

    public function __construct(
    private StudentRepository $students,
    private AbsenceRepository $absences,
    private GradeRepository $grades,
    SerializerInterface $serializer
    ) {
        $this->repository  = $students;    
        $this->serializer  = $serializer;   
    }


    #[Route('/me/semesters/absences', name: 'api_me_semesters_absences', methods: ['GET'])]
    public function meSemestersAbsences(): JsonResponse
    {
        $user = $this->getUser();
        $student = $this->students->findOneBy(['user' => $user]);
        if (!$student) {
            return $this->json(['message' => 'Not a student'], Response::HTTP_NOT_FOUND);
        }

        $blocks = $this->buildAbsenceBlocks($student->getId());
        return $this->json($blocks, Response::HTTP_OK);
    }

    /**
     * Construit les "blocks" d'absences par semestre pour un étudiant.
     * Retourne un ARRAY (pas de JsonResponse).
     */
    private function buildAbsenceBlocks(int $studentId): array
    {
        $student = $this->students->find($studentId);
        if (!$student) {
            return [];
        }

        $semesters = $student->getSemesters(); // Doctrine Collection|array
        $blocks = [];

        // helpers
        $fmt = static fn(int $m) => sprintf('%dh%02d', intdiv(max(0,$m),60), max(0,$m)%60);
        $minutesBetween = static function (? \DateTimeInterface $s, ? \DateTimeInterface $e): int {
            if (!$s || !$e) return 0;
            return max(0, intdiv($e->getTimestamp() - $s->getTimestamp(), 60));
        };
        $normalizeStatus = static function ($a): string {
            $raw = method_exists($a, 'getStatus') ? $a->getStatus() : null;

            // int
            if (is_int($raw)) {
                if ($raw === 4) return 'PENDING';
                if ($raw === 1) return 'JUSTIFIED';
                if ($raw === 3) return 'UNJUSTIFIED';
            }
            // string
            if (is_string($raw) && $raw !== '') {
                $up = strtoupper($raw);
                if ($up === '4' || str_contains($up, 'PENDING'))    return 'PENDING';
                if ($up === '1' || str_contains($up, 'APPROVED') || str_contains($up, 'JUSTIFIED')) return 'JUSTIFIED';
                if ($up === '3' || str_contains($up, 'UNJUSTIFIED')) return 'UNJUSTIFIED';
            }
            // fallback via boolean / présence de justification
            $justified = method_exists($a, 'isJustified') ? (bool)$a->isJustified() : null;
            $hasSubmission =
                (method_exists($a,'getJustifiedAt') && $a->getJustifiedAt()) ||
                (method_exists($a,'getJustificationReason') && $a->getJustificationReason()) ||
                (method_exists($a,'getJustificationComment') && $a->getJustificationComment()) ||
                (method_exists($a,'getJustificationFiles') && $a->getJustificationFiles());

            if ($justified === true) return 'JUSTIFIED';
            if ($hasSubmission)      return 'PENDING';
            return 'UNJUSTIFIED';
        };

        foreach ($semesters as $semester) {
            $absences = $this->absences->findBy([
                'student'  => $student,
                'semester' => $semester,
            ]);

            // Map entités -> DTO pour le front
            $items = array_map(static function ($a) use ($normalizeStatus) {
                $start = method_exists($a, 'getStartedDate') ? $a->getStartedDate()
                    : (method_exists($a, 'getStartedAt') ? $a->getStartedAt() : null);
                $end   = method_exists($a, 'getEndedDate')   ? $a->getEndedDate()
                    : (method_exists($a, 'getEndedAt')   ? $a->getEndedAt()   : null);

                $justified = null;
                if (method_exists($a, 'isJustified')) {
                    $val = $a->isJustified();
                    $justified = is_bool($val) ? $val : (($val === 0 || $val === 1) ? (bool)$val : null);
                }

                // champs de justification (tolérant)
                $files = method_exists($a, 'getJustificationFiles') ? $a->getJustificationFiles() : null;
                if (is_string($files)) {
                    $decoded = json_decode($files, true);
                    $files = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($files)) {
                    $files = [];
                }

                return [
                    'id'                   => $a->getId(),
                    'startedDate'          => $start?->format(\DATE_ATOM),
                    'endedDate'            => $end?->format(\DATE_ATOM),
                    'status'               => $normalizeStatus($a),           // "UNJUSTIFIED" | "PENDING" | "JUSTIFIED"
                    'justified'            => $justified,
                    'justifiedAt'          => method_exists($a,'getJustifiedAt') ? $a->getJustifiedAt()?->format(\DATE_ATOM) : null,
                    'justificationReason'  => method_exists($a,'getJustificationReason') ? $a->getJustificationReason() : null,
                    'justificationComment' => method_exists($a,'getJustificationComment') ? $a->getJustificationComment() : null,
                    'justificationFiles'   => $files,

                    // pour compat’ avec ton ancienne payload
                    'justification'        => method_exists($a, 'getJustification') ? $a->getJustification() : null,
                ];
            }, $absences);

            // Totaux (exclure PENDING du sous-total "injustifiée")
            $minutes = 0; $jMin = 0; $uMin = 0; $jCount = 0; $uCount = 0;

            foreach ($items as $i) {
                $s = !empty($i['startedDate']) ? new \DateTimeImmutable($i['startedDate']) : null;
                $e = !empty($i['endedDate'])   ? new \DateTimeImmutable($i['endedDate'])   : null;
                $m = $minutesBetween($s, $e);
                $minutes += $m;

                if ($i['status'] === 'JUSTIFIED' || $i['justified'] === true) {
                    $jMin += $m; $jCount++;
                } elseif ($i['status'] === 'UNJUSTIFIED') {
                    // PENDING n’entre pas dans "unjustifiée"
                    $uMin += $m; $uCount++;
                }
            }

            $blocks[] = [
                'semester' => [
                    'id'        => $semester->getId(),
                    'name'      => method_exists($semester, 'getName') ? $semester->getName() : null,
                    'startDate' => method_exists($semester, 'getStartDate') ? $semester->getStartDate()?->format('Y-m-d') : null,
                    'endDate'   => method_exists($semester, 'getEndDate') ? $semester->getEndDate()?->format('Y-m-d') : null,
                ],
                'totals' => [
                    'minutes'     => $minutes,
                    'hhmm'        => $fmt($minutes),
                    'justified'   => ['minutes' => $jMin, 'hhmm' => $fmt($jMin), 'count' => $jCount],
                    'unjustified' => ['minutes' => $uMin, 'hhmm' => $fmt($uMin), 'count' => $uCount],
                ],
                'absences' => $items,
            ];
        }

        usort($blocks, function ($a, $b) {
            $ad = $a['semester']['startDate'] ?? null;
            $bd = $b['semester']['startDate'] ?? null;
            if ($ad && $bd) return strcmp($ad, $bd);
            return ($a['semester']['id'] ?? 0) <=> ($b['semester']['id'] ?? 0);
        });

        return $blocks;
    }


    #[Route('/student', name: 'app_student')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/StudentController.php',
        ]);
    }


    #[Route('/students', name: 'student.getAll', methods:['GET'])]
    public function getAllStudents(): JsonResponse
    {
        $student =  $this->repository->findAll();
        $jsonStudents = $this->serializer->serialize($student, 'json',["groups" => "getAllStudents"]);
        return new JsonResponse(    
            $jsonStudents,
            JsonResponse::HTTP_OK, 
            [], 
            true
        );
    }
  
    #[Route('/me/student', name: 'student.me', methods: ['GET'])]
    public function getMyStudent(StudentRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $student = $repo->findOneBy(['user' => $user]);
        if (!$student) {
            return new JsonResponse(['message' => 'No student for this user'], 404);
        }

        // Récupérer les informations de l'utilisateur
        $email = method_exists($user, 'getEmail') ? (string) $user->getEmail() : '';
        $name = method_exists($user, 'getName') ? $user->getName() : null;
        $lastname = method_exists($user, 'getLastname') ? $user->getLastname() : null;

        // Fallback via Student si les champs ne sont pas sur User
        if ((!$name || !$lastname) && method_exists($student, 'getName')) {
            if (!$name) $name = $student->getName();
            if (!$lastname) $lastname = $student->getLastname();
        }

        $fullName = trim(sprintf('%s %s', (string) $name, (string) $lastname));
        $displayName = $fullName !== '' ? $fullName : (str_contains($email, '@') ? explode('@', $email)[0] : 'user');

        return new JsonResponse([
            'id'          => $user->getId(),
            'email'       => $email,
            'roles'       => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'name'        => $name,
            'lastname'    => $lastname,
            'fullName'    => $fullName !== '' ? $fullName : null,
            'displayName' => $displayName,
            'student'     => [
                'id'     => $student->getId(),
                'classe' => $student->getClasse() ? [
                    'id'   => $student->getClasse()->getId(),
                    'name' => $student->getClasse()->getName(),
                ] : null,
            ],
        ], 200);
    }
    
    #[Route('/me/grades', name: 'api_me_grades', methods: ['GET'])]
    public function meGrades(): JsonResponse
    {
        $user = $this->getUser();
        $student = $this->students->findOneBy(['user' => $user]);
        if (!$student) {
            return $this->json(['message' => 'Not a student'], 404);
        }

        $payload = $this->buildGradesPayload($student->getId());
        return $this->json($payload);
    }

    private function buildGradesPayload(int $studentId): array
    {
        $student = $this->students->find($studentId);
        if (!$student) {
            return ['grades' => []];
        }

        $rows = $this->grades->createQueryBuilder('g')
            ->select('
                g.id         AS id,
                g.title      AS title,
                g.grade      AS grade,
                g.dividor    AS dividor,
                c.id         AS course_id,
                c.name       AS course_name,
                c.average    AS course_average
            ')
            ->innerJoin('g.course', 'c')
            ->andWhere('g.student = :sid')
            ->setParameter('sid', $studentId)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('g.title', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $byCourse = [];
        foreach ($rows as $r) {
            $cid = $r['course_id'] ?? null;
            if ($cid === null) {
                continue;
            }
            if (!isset($byCourse[$cid])) {
                $byCourse[$cid] = ['sum20' => 0.0, 'count' => 0];
            }
            $grade   = $r['grade'];
            $dividor = (int)($r['dividor'] ?? 0);
            if ($grade !== null && $dividor > 0) {
                $value20 = (float)$grade / $dividor * 20.0;
                $byCourse[$cid]['sum20'] += $value20;
                $byCourse[$cid]['count']++;
            }
        }

        $courseAvg20 = [];
        foreach ($byCourse as $cid => $acc) {
            $courseAvg20[$cid] = $acc['count'] > 0
                ? round($acc['sum20'] / $acc['count'], 2)
                : null;
        }

        $grades = array_map(function (array $r) use ($courseAvg20) {
            $cid = $r['course_id'] ?? null;
            return [
                'id'      => (int)$r['id'],
                'grade'   => $r['grade'] !== null ? (float)$r['grade'] : null,
                'dividor' => $r['dividor'] !== null ? (int)$r['dividor'] : null,
                'title'   => $r['title'],
                'course'  => [
                    'id'      => $cid !== null ? (int)$cid : null,
                    'name'    => $r['course_name'] ?? null,
                    'average' => $cid !== null ? ($courseAvg20[$cid] ?? null) : null,
                ],
            ];
        }, $rows);

        return ['grades' => $grades];
    }

    #[Route('/student/{id}/absences', name: 'student.getAbsences', methods:['GET'])]
    public function getAbsencesByStudentId(int $id): JsonResponse
    {
        $student = $this->repository->find($id);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        $absences = method_exists($student, 'getAbsences')
            ? $student->getAbsences()->toArray()
            : [];

        $json = $this->serializer->serialize($absences, 'json', ['groups' => 'getStudentAbsences']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

   #[Route('/student', name: 'student.add', methods:['POST'])]
    public function addStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if(!isset($data['classe']) || !isset($data['user']) || !isset($data['semesters'])) {
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }  

        $student = new Student();

        // Classe
        $classe = $this->getClasseFromData($data, $classeRepository);
        if (!$classe) {
            return new JsonResponse(['error' => 'Classe not found or missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $student->setClasse($classe);

        // User
        $user = $this->getUserFromData($data, $userRepository);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found or missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $student->setUser($user);

        // Semesters
        $semesters = $this->getSemestersFromData($data, $semesterRepository);
        foreach ($semesters as $semester) {
            $student->addSemester($semester);
        }

        $em->persist($student);
        $em->flush();

        return new JsonResponse(
            ['message' => 'Student added successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_CREATED
        );
    }


    #[Route(self::ROUTE_FOR_A_STUDENT, name: 'student.update', methods:['PUT'])]
    public function updateStudent(
        Request $request,
        EntityManagerInterface $em,
        ClasseRepository $classeRepository,
        UserRepository $userRepository,
        SemesterRepository $semesterRepository,
        int $id
    ): JsonResponse {
        $student = $this->repository->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Classe
        $classe = $this->getClasseFromData($data, $classeRepository);
        if (isset($data['classe']['id']) && !$classe) {
            return new JsonResponse(['error' => 'Classe not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($classe) {
            $student->setClasse($classe);
        }

        // User
        $user = $this->getUserFromData($data, $userRepository);
        if (isset($data['user']['id']) && !$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($user) {
            $student->setUser($user);
        }

        // Semesters
        if (isset($data['semesters']) && is_array($data['semesters'])) {
            foreach ($student->getSemesters() as $semester) {
                $student->removeSemester($semester);
            }

            foreach ($this->getSemestersFromData($data, $semesterRepository) as $semester) {
                $student->addSemester($semester);
            }
        }

        $em->flush();

        return new JsonResponse(
            ['message' => 'Student updated successfully', 'id' => $student->getId()],
            JsonResponse::HTTP_OK
        );
    }

    #[Route(self::ROUTE_FOR_A_STUDENT, name: 'student.delete', methods:['DELETE'])]
    public function deleteStudent(
        EntityManagerInterface $em,
        int $id
        ): JsonResponse
    {
        $student = $this->repository->find($id);
        $em->remove($student);
        $em->flush();
        return new JsonResponse(
            'Student deleted successfully',
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    private function getClasseFromData(array $data, ClasseRepository $classeRepository) : ?Classe
    {
        if (!isset($data['classe']['id'])) {
            return null;
        }

        return $classeRepository->find($data['classe']['id']);
    }

    private function getUserFromData(array $data, UserRepository $userRepository): ?User
    {
        if (!isset($data['user']['id'])) {
            return null;
        }

        return $userRepository->find($data['user']['id']);
    }

    private function getSemestersFromData(array $data, SemesterRepository $semesterRepository): array
    {
        $semesters = [];

        if (isset($data['semesters']) && is_array($data['semesters'])) {
            foreach ($data['semesters'] as $semesterData) {
                if (isset($semesterData['id'])) {
                    $semester = $semesterRepository->find($semesterData['id']);
                    if ($semester) {
                        $semesters[] = $semester;
                    }
                }
            }
        }

        return $semesters;
    }

}
