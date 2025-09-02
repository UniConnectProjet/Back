<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AbsenceRepository;
use App\Entity\Absence;
use App\Repository\SemesterRepository;
use App\Repository\StudentRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/absences')]
class AbsenceController extends AbstractController
{
    /**
     * Récupère les absences avec des filtres dynamiques.
     * 
     * @Route("/", name="absence.getAll", methods={"GET"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête HTTP contenant les filtres.
     * 
     * @return JsonResponse La liste des absences filtrées.
     */
    #[Route('/', name: 'absence.getAll', methods: ['GET'])]
    public function getAbsences(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        $param = $request->query->all();

        if (isset($param['studentId'])) {
            $param['student'] = $param['studentId'];
            unset($param['studentId']);
        }
        if (isset($param['semesterId'])) {
            $param['semester'] = $param['semesterId'];
            unset($param['semesterId']);
        }

        $absences = $repository->findBy($param);
        $jsonAbsences = $serializer->serialize($absences, 'json', ["groups" => "getAllAbsences"]);

        return new JsonResponse(
            $jsonAbsences,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Récupère toutes les absences pour un semestre donné.
     * 
     * @Route("/semester/{semesterId}", name="absence.getBySemester", methods={"GET"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param int $semesterId L'identifiant du semestre.
     * 
     * @return JsonResponse La liste des absences pour le semestre.
     */
    #[Route('/semester/{semesterId}', name: 'absence.getBySemester', methods: ['GET'])]
    public function getAbsencesBySemester(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        int $semesterId
    ): JsonResponse {
        $absences = $repository->findBy(['semester' => $semesterId]);
        $jsonAbsences = $serializer->serialize($absences, 'json', ["groups" => "getAllAbsences"]);

        return new JsonResponse(
            $jsonAbsences,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Met à jour une absence donnée.
     * 
     * @Route("/{absenceId}", name="absence.update", methods={"PUT"})
     * 
     * @param AbsenceRepository $repository Le repository des absences.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête contenant les données de mise à jour.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $absenceId L'identifiant de l'absence à mettre à jour.
     * 
     * @return JsonResponse L'absence mise à jour.
     */

    #[Route('/{absenceId}', name: 'absence.update', methods: ['PUT'])]
    public function updateAbsence(
        AbsenceRepository $repository,
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $em,
        int $absenceId
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $absence = $repository->find($absenceId);
        if (!$absence) {
            return new JsonResponse(['error' => 'Absence not found'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['justified'])) {
            $absence->setJustified($data['justified']);
        }
        if (isset($data['justification'])) {
            $absence->setJustification($data['justification']);
        }

        $em->persist($absence);
        $em->flush();

        $jsonAbsence = $serializer->serialize($absence, 'json', ["groups" => "getAllAbsences"]);
        return new JsonResponse(
            $jsonAbsence,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Crée une absence pour un étudiant donné.
     * 
     * @Route("/student/{studentId}", name="absence.create", methods={"POST"})
     * 
     * @param StudentRepository $studentRepository Le repository des étudiants.
     * @param SerializerInterface $serializer Le sérialiseur pour transformer les données en JSON.
     * @param Request $request La requête contenant les données de l'absence.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param int $studentId L'identifiant de l'étudiant.
     * 
     * @return JsonResponse L'absence nouvellement créée.
     */
    
    
    #[Route('/student/{studentId}/semester/{semesterId}', name: 'absence.createForStudent', methods: ['POST'])]
    public function createAbsenceForStudent(
        StudentRepository $studentRepository,
        SemesterRepository $semesterRepository,
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $em,
        int $studentId,
        int $semesterId
    ): JsonResponse {
        $student = $studentRepository->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }

        
        $semester = $semesterRepository->find($semesterId);
        if (!$semester) {
             return new JsonResponse(['error' => 'Semester not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();
        $absence = $serializer->deserialize($data, Absence::class, 'json');
        $absence->setStudent($student);
        $absence->setSemester($semester);

        $em->persist($absence);
        $em->flush();

        $jsonAbsence = $serializer->serialize($absence, 'json', ["groups" => "getAllAbsences"]);
        return new JsonResponse(
            $jsonAbsence,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{absenceId}', name: 'absence.deleteAbsence', methods: ['DELETE'])]
    public function deleteAbsence(
        AbsenceRepository $repository,
        EntityManagerInterface $em,
        int $absenceId
    ): JsonResponse {
        $absence = $repository->find($absenceId);
        if (!$absence) {
            return new JsonResponse(['error' => 'Absence not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($absence);
        $em->flush();

        return new JsonResponse(['message' => 'Absence deleted successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}/justify', name: 'api_absence_justify', methods: ['POST'])]
    public function justify(
        int $id,
        Request $request,
        AbsenceRepository $absences,
        EntityManagerInterface $em,
        Security $security,                    // OK en Symfony 6+
        SluggerInterface $slugger,
        string $justificationsDir,             // bindé dans services.yaml
    ): JsonResponse {
        $absence = $absences->find($id);
        if (!$absence) {
            return $this->json(['message' => 'Absence introuvable'], 404);
        }

        // --- Autorisation: étudiant propriétaire OU admin
        $user = $security->getUser();
        $isOwner = method_exists($absence, 'getStudent')
            && $absence->getStudent()
            && method_exists($absence->getStudent(), 'getUser')
            && $absence->getStudent()->getUser() === $user;
        $isAdmin = $user && \in_array('ROLE_ADMIN', $user->getRoles(), true);

        if (!$isOwner && !$isAdmin) {
            return $this->json(['message' => 'Accès refusé'], 403);
        }

        // --- Règle métier: dépôt autorisé si UNJUSTIFIED ou REJECTED
        if (!\in_array($absence->getStatus(), [Absence::STATUS_UNJUSTIFIED, Absence::STATUS_REJECTED], true)) {
            return $this->json(['message' => 'Justification déjà déposée ou en cours de revue'], 409);
        }

        // --- Payload
        $reason  = trim((string) $request->request->get('reason', ''));
        $comment = $request->request->get('comment');
        if ($reason === '') {
            return $this->json(['errors' => ['reason' => 'La raison est requise']], 422);
        }

        // --- Fichiers (attachments OU attachments[])
        $filesParam = $request->files->get('attachments', []);
        if ($filesParam instanceof UploadedFile) {
            $filesParam = [$filesParam];
        } elseif (!\is_array($filesParam)) {
            $filesParam = [];
        }
        /** @var UploadedFile[] $files */
        $files = array_values(array_filter($filesParam, fn($f) => $f instanceof UploadedFile));
        if (\count($files) > 3) {
            return $this->json(['errors' => ['attachments' => 'Maximum 3 fichiers']], 422);
        }

        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5 Mo

        $fs = new Filesystem();
        $target = rtrim($justificationsDir, '/').'/'.$absence->getId();
        if (!$fs->exists($target)) {
            $fs->mkdir($target, 0700);
        }

        $filesMeta = [];
        foreach ($files as $f) {
            $mime = $f->getClientMimeType() ?? $f->getMimeType();
            $size = $f->getSize() ?? 0;

            if ($mime && !\in_array($mime, $allowed, true)) {
                return $this->json(['errors' => ['attachments' => 'PDF/JPG/PNG uniquement']], 422);
            }
            if ($size > $maxSize) {
                return $this->json(['errors' => ['attachments' => 'Taille max: 5 Mo']], 422);
            }

            $base = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
            $safe = (string) $slugger->slug($base);
            $ext  = $f->guessExtension() ?: $f->getClientOriginalExtension() ?: 'bin';
            $name = sprintf('%s-%s.%s', $safe, bin2hex(random_bytes(4)), $ext);

            $f->move($target, $name);

            $filesMeta[] = [
                'name' => $f->getClientOriginalName(),
                'path' => $name,     // chemin relatif
                'size' => $size,
                'mime' => $mime,
            ];
        }

        // --- MAJ de l’absence
        $absence->setJustificationReason($reason);
        $absence->setJustificationComment($comment);
        $absence->setJustificationFiles($filesMeta);
        $absence->setStatus(Absence::STATUS_PENDING);   // en attente admin
        $absence->setJustifiedAt(new \DateTime());      // \DateTime mutable

        if (method_exists($absence, 'setJustifiedBy'))  $absence->setJustifiedBy($user);
        if (method_exists($absence, 'setJustified'))    $absence->setJustified(false); // bool legacy
        if (method_exists($absence, 'setReviewComment')) $absence->setReviewComment(null);
        if (method_exists($absence, 'setReviewedAt'))    $absence->setReviewedAt(null);
        if (method_exists($absence, 'setReviewedBy'))    $absence->setReviewedBy(null);

        $em->flush();

        return $this->json([
            'id'          => $absence->getId(),
            'status'      => $absence->getStatus(),
            'reason'      => $absence->getJustificationReason(),
            'comment'     => $absence->getJustificationComment(),
            'files'       => $filesMeta,
            'justifiedAt' => $absence->getJustifiedAt()?->format(\DATE_ATOM),
        ], 200);
    }

    #[Route('/me/unjustified', name: 'api_me_absences_unjustified', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function myUnjustified(
        Request $request,
        AbsenceRepository $repo,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $student = method_exists($user, 'getStudent') ? $user->getStudent() : null;
        $studentId = $student?->getId() ?: (method_exists($user, 'getId') ? (int) $user->getId() : 0);
        if (!$studentId) {
            return $this->json(['error' => 'No student attached to the current user'], 400);
        }

        $semesterId = $request->query->get('semesterId');

        // UNJUSTIFIED (3 / "UNJUSTIFIED") en excluant PENDING (4 / "PENDING")
        $qb = $repo->createQueryBuilder('a')
            ->andWhere('a.student = :sid')->setParameter('sid', $studentId)
            ->andWhere('(a.status = 3 OR a.status = :u1 OR a.status = :u2 OR a.justified = 0)')
                ->setParameter('u1', 'UNJUSTIFIED')->setParameter('u2', 'unjustified')
            ->andWhere('(a.status <> 4 AND a.status <> :p1 AND a.status <> :p2)')
                ->setParameter('p1', 'PENDING')->setParameter('p2', 'pending')
            ->orderBy('a.startedDate', 'ASC');

        if ($semesterId) {
            $qb->andWhere('a.semester = :sem')->setParameter('sem', (int) $semesterId);
        }

        $rows = $qb->getQuery()->getResult();

        $data = array_map(static function (Absence $a) {
            return [
                'id'                   => $a->getId(),
                'startedDate'          => $a->getStartedDate()?->format(\DateTimeInterface::ATOM),
                'endedDate'            => $a->getEndedDate()?->format(\DateTimeInterface::ATOM),
                'status'               => $a->getStatus(),
                'justified'            => (bool) ($a->isJustified() ?? false),
                'justificationReason'  => $a->getJustificationReason(),
                'justificationComment' => $a->getJustificationComment(),
                'justificationFiles'   => $a->getJustificationFiles(),
                'semester'             => $a->getSemester()?->getId(),
                'courseSession'        => $a->getCourseSession()?->getId(),
            ];
        }, $rows);

        return $this->json(['count' => \count($data), 'data' => $data], 200);
    }
}