<?php

namespace App\DataFixtures;

use App\Entity\Absence;
use App\Entity\Category;
use App\Entity\Classe;
use App\Entity\Conversation;
use App\Entity\Course;
use App\Entity\CourseUnit;
use App\Entity\Grade;
use App\Entity\Level;
use App\Entity\Message;
use App\Entity\Semester;
use App\Entity\Student;
use App\Entity\CourseSession;
use App\Entity\Professor;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use PhpParser\Node\Expr\Cast\Array_;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
// Lancer les fixtures avec `php bin/console doctrine:fixtures:load --group=dev -n`
class AppFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $passwordHasher;
    private int $nbCategories;
    private int $nbNiveaux;
    private int $classesParCombo;
    private int $studentsParClasse;
    private int $nbClasses = 5;
    private \Faker\Generator $faker;
    private array $classesByCategory = [];

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }

    private function initParameters(bool $isLight): void
    {
        $this->nbCategories = $isLight ? 2 : 2; // Seulement 2 catégories
        $this->nbNiveaux = $isLight ? 2 : 2; // Seulement 2 niveaux
        $this->classesParCombo = 5; // 5 classes par catégorie
        $this->studentsParClasse = 10; // 10 élèves par classe
    }


    private function createLevels(ObjectManager $manager): array{
        // Niveaux d'études
        $niveaux = array_slice([
            'Bac+1', 'Bac+2', 'Bac+3', 'Bac+4', 'Bac+5', 'Doctorat'
        ], 0, $this->nbNiveaux);

        $levels = [];
        foreach ($niveaux as $nom) {
            $level = new Level();
            $level->setName($nom);
            $manager->persist($level);
            $levels[] = $level;
        }
        return $levels;
    }
    private function createCategories(ObjectManager $manager, array $levels): array {
        // Catégories
        $categoryNames = array_slice([
            'Informatique', 'Chimie', 'Génie Civil', 'Biologie', 'Mathématiques', 'Electronique', 'Physique', 'Gestion'
        ], 0, $this->nbCategories);

        $categories = [];
        foreach ($categoryNames as $catName) {
            $category = new Category();
            $category->setName($catName);
            foreach ($levels as $level) {
                $category->addLevelId($level);
            }
            $manager->persist($category);
            $categories[] = $category;
        }
        return $categories;
    }

    private function createClasses(ObjectManager $manager, array $categories, array $levels): array { 
        // Classes
        $classes = [];
        $classesByCategory = [];
        foreach ($categories as $category) {
            foreach ($levels as $level) {
                for ($i = 1; $i <= $this->classesParCombo; $i++) {
                    $classe = new Classe();
                    $classe->setName("{$category->getName()} - {$level->getName()} - Classe $i");
                    $classe->setCategory($category);
                    $classe->setLevelId($level);
                    $manager->persist($classe);
                    $classes[] = $classe;
                    $classesByCategory[$category->getName()][] = $classe;
                }
            }
        }
        
        // Stocker les classes par catégorie dans une propriété de classe pour y accéder plus tard
        $this->classesByCategory = $classesByCategory;
        
        return $classes;
    }

    private function createSemesters(ObjectManager $manager): array {
        // Semestres avec dates précises
        $semesters = [];
        
        // Semestre 1 : 2 septembre 2025 au 21 janvier 2026
        $semester1 = new Semester();
        $semester1->setName("Semestre 1");
        $semester1->setStartDate(new \DateTime('2025-09-02 00:00:00'));
        $semester1->setEndDate(new \DateTime('2026-01-21 23:59:59'));
        $manager->persist($semester1);
        $semesters[] = $semester1;
        
        // Semestre 2 : 2 février 2026 au 26 juin 2026
        $semester2 = new Semester();
        $semester2->setName("Semestre 2");
        $semester2->setStartDate(new \DateTime('2026-02-02 00:00:00'));
        $semester2->setEndDate(new \DateTime('2026-06-26 23:59:59'));
        $manager->persist($semester2);
        $semesters[] = $semester2;
        
        return $semesters;
    }

    private function createUsers(ObjectManager $manager, array $classes, array $semesters): array
    {
        $users = [];
        // Créer exactement 50 étudiants (10 par classe × 5 classes)
        $totalStudents = 50;
        
        for ($j = 0; $j < $totalStudents; $j++) {
            $user = new User();
            $user->setName($this->faker->firstName());
            $user->setLastname($this->faker->lastName());
            $user->setBirthday($this->faker->dateTimeBetween('-25 years', '-18 years'));
            $user->setEmail($this->faker->unique()->safeEmail());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_STUDENT']);
            $manager->persist($user);
            $users[] = $user;
        }
        return $users;
    }

    private function createProfessors(ObjectManager $manager, array $categories, int $count = 20): array
    {
        $professors = [];
        
        // Répartir les professeurs par catégorie
        $professorsPerCategory = intval($count / count($categories));
        $remainingProfessors = $count % count($categories);
        
        $professorIndex = 1;
        
        foreach ($categories as $categoryIndex => $category) {
            // Calculer le nombre de professeurs pour cette catégorie
            $professorsForThisCategory = $professorsPerCategory;
            if ($categoryIndex < $remainingProfessors) {
                $professorsForThisCategory++;
            }
            
            for ($i = 0; $i < $professorsForThisCategory; $i++) {
                $user = new User();
                $user->setName('Prof' . $professorIndex);
                $user->setLastname($this->faker->lastName());
                $user->setBirthday($this->faker->dateTimeBetween('-60 years', '-30 years'));
                $user->setEmail(sprintf('prof%02d@example.com', $professorIndex));
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

                $roles = $user->getRoles();
                if (!in_array('ROLE_PROFESSOR', $roles, true)) {
                    $roles[] = 'ROLE_PROFESSOR';
                }
                $user->setRoles($roles);

                $manager->persist($user);

                $prof = new Professor();
                $prof->setUserId($user);
                
                // Affecter le professeur à la catégorie
                $prof->addCategory($category);

                if (method_exists($prof, 'setWeeklyAvailability')) {
                    $prof->setWeeklyAvailability([
                        'MON' => [['08:00','12:00']],
                        'TUE' => [['10:00','12:00'], ['14:00','16:00']],
                        'WED' => [['09:00','11:00']],
                        'THU' => [['13:00','16:00']],
                        'FRI' => [['14:00','17:00']],
                        'SAT' => [], 'SUN' => [],
                    ]);
                }

                $manager->persist($prof);

                $professors[] = $prof;
                $professorIndex++;
            }
        }
        
        return $professors;
    }


    private function createStudents(ObjectManager $manager, array $classes, array $semesters, array $users): array
    {
        $students = [];

        $targetClasses = array_slice($classes, 0, 5);
        if (!$targetClasses) {
            return $students;
        }

        $nbTargets = count($targetClasses); // ≤ 5
        $i = 0;

        foreach ($users as $user) {
            $student = new Student();
            $student->setUser($user);

            $classe = $targetClasses[$i % $nbTargets];
            if (method_exists($student, 'setClasse')) {
                $student->setClasse($classe);
            }

            foreach ($semesters as $semester) {
                $student->addSemester($semester);
            }

            $manager->persist($student);
            $students[] = $student;
            $i++;
        }

        return $students;
    }

    
    private function createCourses(ObjectManager $manager, array $categories, array $levels, array $semesters, array $classes, array $professors): array { 
        // Courses par catégorie
        $courseParCategorie = [
            'Informatique' => [
                'UE Programmation' => ['Programmation Web', 'POO', 'Git', 'DevOps', 'Tests'],
                'UE Algorithmique' => ['Algorithmes', 'Structures de données', 'Complexité', 'Graphes', 'Optimisation'],
                'UE Systèmes' => ['Systèmes Unix', 'Réseaux', 'Sécurité', 'Virtualisation', 'Docker'],
                'UE Base de données' => ['SQL', 'Modélisation', 'NoSQL', 'Transactions', 'Requêtes avancées'],
                'UE Web' => ['HTML/CSS', 'JavaScript', 'Symfony', 'API REST', 'React'],
            ],
            'Chimie' => [
                'UE Générale' => ['Chimie Générale', 'Thermochimie', 'Oxydoréduction', 'pH', 'Solubilité'],
                'UE Organique' => ['Chimie Orga 1', 'Chimie Orga 2', 'Synthèse', 'Isomérie', 'Spectroscopie'],
                'UE Analytique' => ['Chromatographie', 'Spectroscopie UV', 'Analyse qualitative', 'Titrage', 'Electrochimie'],
                'UE Minérale' => ['Composés ioniques', 'Complexes', 'Métaux', 'Précipités', 'Analyse minérale'],
                'UE Expérimentale' => ['TP Orga', 'TP Minérale', 'Sécurité labo', 'Protocoles', 'Bilan matières'],
            ],
            'Génie Civil' => [
                'UE Structures' => ['Statique', 'RDM', 'Structures béton', 'Structures acier', 'Calculs éléments finis'],
                'UE Matériaux' => ['Béton armé', 'Acier', 'Verre', 'Bois', 'Normes'],
                'UE Topographie' => ['Nivellement', 'Mesures', 'GPS', 'Relevé terrain', 'Cartographie'],
                'UE Construction' => ['Planification', 'Chantier', 'Coût', 'Sécurité', 'Logistique'],
                'UE DAO' => ['AutoCAD', 'Revit', 'SketchUp', 'Plan 2D', 'Maquette 3D'],
            ],
            'Biologie' => [
                'UE Cellule' => ['Biologie Cellulaire', 'ADN/ARN', 'Cycle cellulaire', 'Division', 'Culture cellulaire'],
                'UE Génétique' => ['Hérédité', 'Mutation', 'Caryotype', 'Cartes génétiques', 'Technologie ADN'],
                'UE Microbio' => ['Bactérie', 'Virus', 'Stérilisation', 'Antibio', 'Croissance microbienne'],
                'UE Biochimie' => ['Protéines', 'Enzymes', 'Lipides', 'Glucides', 'Voies métaboliques'],
                'UE Environnement' => ['Écosystèmes', 'Écologie', 'Cycle du carbone', 'Biodiversité', 'Pollution'],
            ],
            'Mathématiques' => [
                'UE Analyse' => ['Dérivées', 'Intégrales', 'Limites', 'Suites', 'Séries'],
                'UE Algèbre' => ['Matrices', 'Espaces vectoriels', 'Applications linéaires', 'Déterminants', 'Réduction'],
                'UE Proba/Stats' => ['Variable aléatoire', 'Lois usuelles', 'Espérance', 'Échantillonnage', 'Tests'],
                'UE Géométrie' => ['Vecteurs', 'Plans', 'Angles', 'Distances', 'Transformations'],
                'UE Informatique' => ['Python', 'Maths appliquées', 'Numérique', 'Calcul formel', 'Logique'],
            ],
        ];

        $courseUnits = [];
        $courses = [];

        // Créer une map pour retrouver la catégorie par son nom
        $categoryByName = [];
        foreach ($categories as $category) {
            $categoryByName[$category->getName()] = $category;
        }

        // Créer une map pour retrouver les professeurs par catégorie
        $professorsByCategory = [];
        foreach ($professors as $professor) {
            foreach ($professor->getCategories() as $category) {
                $professorsByCategory[$category->getName()][] = $professor;
            }
        }

        foreach ($courseParCategorie as $categorieNom => $ues) {
            $category = $categoryByName[$categorieNom] ?? null;
            if (!$category) {
                continue;
            }

            // Récupérer les professeurs de cette catégorie
            $categoryProfessors = $professorsByCategory[$categorieNom] ?? [];

            foreach ($ues as $ueName => $moduleNames) {
                $courseUnit = new CourseUnit();
                $courseUnit->setName($ueName);
                $courseUnit->setSemester($semesters[array_rand($semesters)]);
                $courseUnit->setAverage(mt_rand(10, 20));
                $courseUnit->setAverageScore(mt_rand(10, 20));
                $courseUnit->setCategory($category);
                $randomLevel = $levels[array_rand($levels)];
                $courseUnit->setCategory($category);
                $courseUnit->setLevels($randomLevel);

                $manager->persist($courseUnit);
                $courseUnits[] = $courseUnit;

                foreach ($moduleNames as $moduleName) {
                    $course = new Course();
                    $course->setName($moduleName);
                    $course->setAverage(mt_rand(10, 20));
                    $course->setCourseUnit($courseUnit);

                    // Lier le cours aux classes de cette catégorie
                    if (isset($this->classesByCategory[$categorieNom])) {
                        foreach ($this->classesByCategory[$categorieNom] as $classe) {
                            $course->addClass($classe);
                        }
                    }

                    // Lier le cours aux professeurs de cette catégorie
                    foreach ($categoryProfessors as $professor) {
                        $course->addProfessor($professor);
                    }

                    $manager->persist($course);
                    $courses[] = $course;
                }
            }
        }
        return $courses;
    }

    private function createGrades(ObjectManager $manager, array $students, array $courses): void
    {
        // Types de contrôles avec leurs diviseurs typiques
        $controlTypes = [
            'DS' => [20, 20, 20, 20, 20], // Devoir surveillé - toujours sur 20
            'TP' => [20, 20, 20, 20, 20], // Travaux pratiques - sur 20
            'Quiz' => [10, 10, 10, 10, 10], // Quiz - sur 10
            'Projet' => [20, 20, 20, 20, 20], // Projet - sur 20
            'Contrôle' => [30, 30, 30, 30, 30], // Contrôle - sur 30
            'Examen' => [20, 20, 20, 20, 20], // Examen - sur 20
            'Devoir' => [20, 20, 20, 20, 20], // Devoir maison - sur 20
        ];

        // Dates de création récentes pour les tests
        $baseDate = new \DateTimeImmutable('2025-01-01');
        $dateRange = [
            $baseDate->modify('-30 days'), // Il y a 30 jours
            $baseDate->modify('-20 days'), // Il y a 20 jours
            $baseDate->modify('-10 days'), // Il y a 10 jours
            $baseDate->modify('-5 days'),  // Il y a 5 jours
            $baseDate->modify('-2 days'),  // Il y a 2 jours
            $baseDate->modify('-1 day'),   // Hier
            $baseDate,                     // Aujourd'hui
        ];

        foreach ($students as $student) {
            $studentClasse = $student->getClasse();
            
            $eligible = array_filter($courses, function ($course) use ($studentClasse) {
                // cas le plus courant: ManyToMany "classes"
                if (method_exists($course, 'getClasses') && $course->getClasses() !== null) {
                    return $course->getClasses()->contains($studentClasse);
                }
                if (method_exists($course, 'getClasse')) {
                    $cl = $course->getClasse();
                    if ($cl instanceof \Doctrine\Common\Collections\Collection) {
                        return $cl->contains($studentClasse);
                    }
                    return $cl === $studentClasse;
                }
                return true;
            });

            $toPick = array_values($eligible);
            shuffle($toPick);
            $toPick = array_slice($toPick, 0, min(random_int(2, 4), count($toPick)));

            foreach ($toPick as $course) {
                // Créer 2-4 contrôles par cours
                $nbControls = random_int(2, 4);
                
                for ($i = 0; $i < $nbControls; $i++) {
                    $controlType = array_rand($controlTypes);
                    $dividorChoices = $controlTypes[$controlType];
                    $dividor = $dividorChoices[array_rand($dividorChoices)];
                    
                    // Générer une note réaliste (éviter les notes trop basses ou trop hautes)
                    $minGrade = max(0, $dividor * 0.3); // Minimum 30% de la note max
                    $maxGrade = min($dividor, $dividor * 0.95); // Maximum 95% de la note max
                    $grade = random_int((int)$minGrade, (int)$maxGrade);

                    $g = new Grade();
                    $g->setStudent($student);
                    $g->setCourse($course);
                    $g->setTitle($controlType . ' ' . ($i + 1));
                    $g->setDividor($dividor);
                    $g->setGrade($grade);
                    
                    // Définir une date de création aléatoire
                    $createdAt = $dateRange[array_rand($dateRange)];
                    $g->setCreatedAt($createdAt);

                    $manager->persist($g);
                }
            }
        }
    }


    private function createConversations(ObjectManager $manager, array $students, array $professors): array
    {
        $conversations = [];
        
        // Récupérer les utilisateurs des étudiants et professeurs
        $studentUsers = [];
        $professorUsers = [];
        
        foreach ($students as $student) {
            if (method_exists($student, 'getUser') && $student->getUser()) {
                $studentUsers[] = $student->getUser();
            }
        }
        
        foreach ($professors as $professor) {
            if (method_exists($professor, 'getUserId') && $professor->getUserId()) {
                $professorUsers[] = $professor->getUserId();
            }
        }
        
        if (empty($studentUsers) || empty($professorUsers)) {
            return $conversations;
        }
        
        // Conversations individuelles étudiant-professeur uniquement
        for ($i = 0; $i < min(20, count($studentUsers)); $i++) {
            $student = $studentUsers[$i];
            $professor = $professorUsers[array_rand($professorUsers)];
            
            $conversation = new Conversation();
            $conversation->setTitle("Discussion avec " . $professor->getName() . " " . $professor->getLastname());
            $conversation->addParticipant($student);
            $conversation->addParticipant($professor);
            
            $manager->persist($conversation);
            $conversations[] = $conversation;
            
            // Ajouter quelques messages d'exemple
            $this->addSampleMessages($manager, $conversation, [$student, $professor]);
        }
        
        return $conversations;
    }
    
    private function addSampleMessages(ObjectManager $manager, Conversation $conversation, array $participants): void
    {
        $messageTemplates = [
            'Bonjour Professeur !',
            'J\'ai une question sur le cours d\'aujourd\'hui',
            'Merci pour l\'explication !',
            'À quelle heure est le cours demain ?',
            'J\'ai un problème avec l\'exercice...',
            'Les notes sont disponibles sur la plateforme',
            'Pouvez-vous m\'expliquer ce chapitre ?',
            'Parfait, merci beaucoup !',
            'Je vais regarder ça et je vous tiens au courant',
            'Excellente question !',
            'Pouvez-vous m\'envoyer le fichier du cours ?',
            'D\'accord, on se voit demain alors',
            'J\'ai trouvé la solution !',
            'Pouvez-vous m\'aider avec le projet ?',
            'Merci pour votre aide ! 🙏'
        ];
        
        $nbMessages = $this->faker->numberBetween(5, 15);
        
        for ($i = 0; $i < $nbMessages; $i++) {
            $message = new Message();
            $message->setContent($this->faker->randomElement($messageTemplates));
            $message->setSender($this->faker->randomElement($participants));
            $message->setConversation($conversation);
            $message->setIsRead($this->faker->boolean(70)); // 70% de chance d'être lu
            
            // Décaler les dates pour simuler une conversation sur plusieurs jours
            $createdAt = new \DateTime();
            $createdAt->modify('-' . $this->faker->numberBetween(0, 7) . ' days');
            $createdAt->modify('-' . $this->faker->numberBetween(0, 23) . ' hours');
            $createdAt->modify('-' . $this->faker->numberBetween(0, 59) . ' minutes');
            $message->setCreatedAt($createdAt);
            
            $manager->persist($message);
        }
    }

    private function createAbsences(ObjectManager $manager, array $students, array $semesters): void
    {
        $tzName      = \date_default_timezone_get();
        $minHour     = 8;
        $maxHour     = 19;
        $durations   = [60, 120];
        $minSlots    = 1;
        $maxSlots    = 3;
        $minuteStep  = 60;

        $possibleMinutes = ($minuteStep >= 60) ? [0] : range(0, 59, max(1, min(59, $minuteStep)));

        $sessionRepo = $manager->getRepository(CourseSession::class);
        /** @var array<int, CourseSession[]> $sessionsByClasseId */
        $sessionsByClasseId = [];

        foreach ($students as $student) {
            /** @var Student $student */
            $classe   = method_exists($student, 'getClasse') ? $student->getClasse() : null;
            $classeId = $classe?->getId();

            if ($classeId && !isset($sessionsByClasseId[$classeId])) {
                $sessionsByClasseId[$classeId] = $sessionRepo->createQueryBuilder('s')
                    ->andWhere('s.classe = :c')->setParameter('c', $classe)
                    ->orderBy('s.startAt', 'ASC')
                    ->getQuery()->getResult();
            }

            $day = $this->faker->dateTimeThisYear('now', $tzName);
            $day->setTimezone(new \DateTimeZone($tzName));
            $baseDay     = (clone $day)->setTime(0, 0, 0);
            $targetSlots = $this->faker->numberBetween($minSlots, $maxSlots);
            $intervals   = [];

            $attempts = 0;
            while (count($intervals) < $targetSlots && $attempts < 32) {
                $attempts++;

                $duration = $this->faker->randomElement($durations);
                // borne haute réaliste pour ne jamais dépasser $maxHour
                $lastStartHour = max($minHour, $maxHour - intdiv($duration, 60));
                if ($lastStartHour < $minHour) break;

                $startHour   = $this->faker->numberBetween($minHour, $lastStartHour);
                $startMinute = $this->faker->randomElement($possibleMinutes);

                $start = (clone $baseDay)->setTime($startHour, $startMinute, 0);
                $end   = (clone $start)->modify("+{$duration} minutes");

                if ($end->format('Y-m-d') !== $baseDay->format('Y-m-d')) continue;

                $overlap = false;
                foreach ($intervals as [$s, $e]) {
                    if ($start < $e && $end > $s) { $overlap = true; break; }
                }
                if ($overlap) continue;

                $intervals[] = [$start, $end];
            }

            usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

            foreach ($intervals as [$start, $end]) {
                $session = null;
                if ($classeId && !empty($sessionsByClasseId[$classeId])) {
                    // priorité: séance qui chevauche l'intervalle le même jour
                    foreach ($sessionsByClasseId[$classeId] as $s) {
                        $sStart = method_exists($s, 'getStartAt') ? $s->getStartAt() : null;
                        $sEnd   = method_exists($s, 'getEndAt')   ? $s->getEndAt()   : null;
                        if ($sStart && $sEnd
                            && $sStart->format('Y-m-d') === $start->format('Y-m-d')
                            && $sStart < $end && $sEnd > $start) { $session = $s; break; }
                    }
                    if (!$session) {
                        foreach ($sessionsByClasseId[$classeId] as $s) {
                            $sStart = method_exists($s, 'getStartAt') ? $s->getStartAt() : null;
                            if ($sStart && $sStart->format('Y-m-d') === $start->format('Y-m-d')) { $session = $s; break; }
                        }
                    }
                    if (!$session) {
                        $session = $sessionsByClasseId[$classeId][array_rand($sessionsByClasseId[$classeId])];
                    }
                } else {
                    continue;
                }

                // Aligne l'absence sur l'horaire de la séance + convertit en \DateTime (mutable)
                $sStart = method_exists($session, 'getStartAt') ? $session->getStartAt() : null;
                $sEnd   = method_exists($session, 'getEndAt')   ? $session->getEndAt()   : null;
                if (!$sStart || !$sEnd) continue;

                $toMutable = static function (? \DateTimeInterface $d): ?\DateTime {
                    if ($d instanceof \DateTime) return clone $d;
                    if ($d instanceof \DateTimeImmutable) return \DateTime::createFromImmutable($d);
                    return null;
                };
                $start = $toMutable($sStart);
                $end   = $toMutable($sEnd);
                if (!$start || !$end) continue;

                // anti-doublon (même étudiant + même séance)
                if ($manager->getRepository(Absence::class)->findOneBy([
                    'student'       => $student,
                    'courseSession' => $session,
                ])) {
                    continue;
                }

                // Trouver le semestre
                $semester = null;
                $course   = method_exists($session, 'getCourse') ? $session->getCourse() : null;
                if ($course && method_exists($course, 'getSemester')) {
                    $semester = $course->getSemester();
                }
                if (!$semester && !empty($semesters)) {
                    foreach ($semesters as $sem) {
                        $semStart = method_exists($sem, 'getStartDate') ? $sem->getStartDate() : null;
                        $semEnd   = method_exists($sem, 'getEndDate')   ? $sem->getEndDate()   : null;
                        if ($semStart instanceof \DateTimeImmutable) $semStart = \DateTime::createFromImmutable($semStart);
                        if ($semEnd   instanceof \DateTimeImmutable) $semEnd   = \DateTime::createFromImmutable($semEnd);
                        if ($semStart && $semEnd && $start >= $semStart && $end <= $semEnd) { $semester = $sem; break; }
                    }
                }
                if (!$semester && !empty($semesters)) {
                    $semester = $semesters[array_rand($semesters)];
                }

                // Création de l'absence
                $absence = new Absence();
                $absence->setStudent($student);
                $absence->setCourseSession($session);
                $absence->setStartedDate($start);
                $absence->setEndedDate($end);
                if ($semester && method_exists($absence, 'setSemester')) {
                    $absence->setSemester($semester);
                }

                // le reste inchangé
                $justified = $this->faker->boolean();
                $absence->setJustified($justified);
                if ($justified) {
                    if (method_exists($absence, 'setJustification')) {
                        $absence->setJustification($this->faker->sentence());
                    } elseif (method_exists($absence, 'setJustificationNote')) {
                        $absence->setJustificationNote($this->faker->sentence());
                    }
                }

                if (method_exists($absence, 'setStatus')) {
                    if (method_exists($absence, 'isJustified') && $absence->isJustified()) {
                        $absence->setStatus(Absence::STATUS_APPROVED);
                    } elseif (!method_exists($absence, 'getStatus') || $absence->getStatus() === null) {
                        $absence->setStatus(Absence::STATUS_UNJUSTIFIED);
                    }
                }

                // Ajouter les nouveaux champs pour l'appel
                $presenceStatuses = ['PRESENT', 'ABSENT', 'LATE'];
                $presenceStatus = $this->faker->randomElement($presenceStatuses);
                
                if (method_exists($absence, 'setPresenceStatus')) {
                    $absence->setPresenceStatus($presenceStatus);
                }
                
                // Minutes de retard si LATE
                $minutesLate = 0;
                if ($presenceStatus === 'LATE') {
                    $minutesLate = $this->faker->numberBetween(5, 60);
                }
                if (method_exists($absence, 'setMinutesLate')) {
                    $absence->setMinutesLate($minutesLate);
                }
                
                // Note du professeur
                if (method_exists($absence, 'setJustificationNote')) {
                    if ($presenceStatus !== 'PRESENT' && $this->faker->boolean(30)) {
                        $absence->setJustificationNote($this->faker->sentence());
                    }
                }
                
                // Professeur qui a enregistré l'appel
                if (method_exists($absence, 'setRecordedBy')) {
                    $professor = $session->getProfessor();
                    if ($professor && method_exists($professor, 'getUser')) {
                        $absence->setRecordedBy($professor->getUser());
                    }
                }

                if (method_exists($absence, 'setJustificationReason') && $absence->getJustificationReason() === null) {
                    $absence->setJustificationReason(null);
                }
                if (method_exists($absence, 'setJustificationComment') && $absence->getJustificationComment() === null) {
                    $absence->setJustificationComment(null);
                }
                if (method_exists($absence, 'setJustificationFiles') && $absence->getJustificationFiles() === null) {
                    $absence->setJustificationFiles([]);
                }
                if (method_exists($absence, 'setJustifiedAt') && $absence->getJustifiedAt() === null) {
                    $absence->setJustifiedAt(null);
                }
                if (method_exists($absence, 'setJustifiedBy') && $absence->getJustifiedBy() === null) {
                    $absence->setJustifiedBy(null);
                }
                if (method_exists($absence, 'setReviewComment') && $absence->getReviewComment() === null) {
                    $absence->setReviewComment(null);
                }
                if (method_exists($absence, 'setReviewedAt') && $absence->getReviewedAt() === null) {
                    $absence->setReviewedAt(null);
                }
                if (method_exists($absence, 'setReviewedBy') && $absence->getReviewedBy() === null) {
                    $absence->setReviewedBy(null);
                }

                $manager->persist($absence);
            }
        }
    }


    // Utils
    private function hasRole(User $u, string $role): bool {
        return in_array($role, $u->getRoles() ?? [], true);
    }

    private function assignStudentsToClasses(ObjectManager $manager): void
    {
        $classeRepo  = $manager->getRepository(Classe::class);
        $studentRepo = $manager->getRepository(Student::class);
        $userRepo    = $manager->getRepository(User::class);

        // 1) Récupère les classes
        /** @var Classe[] $classes */
        $classes = $classeRepo->findAll();
        if (!$classes) return;

        // 2) Garde seulement les users qui sont des étudiants
        /** @var User[] $users */
        $users = $userRepo->findAll();
        $studentUsers = array_values(array_filter(
            $users,
            fn (User $u) => $this->hasRole($u, 'ROLE_STUDENT')
        ));
        if (!$studentUsers) return;

        // 3) Assigne en round-robin : 0,1,2,... puis on recommence
        $nbClasses = count($classes);
        $i = 0;

        foreach ($studentUsers as $user) {
            // a) retrouve ou crée l'entité Student liée à ce user
            $student = $studentRepo->findOneBy(['user' => $user]);
            if (!$student) {
                $student = new Student();
                $student->setUser($user);
            }

            // b) choix de la classe (équilibré)
            $classe = $classes[$i % $nbClasses];

            // c) affectation
            if (method_exists($student, 'setClasse')) {
                $student->setClasse($classe);
            }

            $manager->persist($student);
            $i++;
        }
}


    private function seedCourseSessions(ObjectManager $manager, array $courses, array $classes, array $professors): array
    {
        if (!$courses || !$classes || !$professors) return [];

        $created = [];

        // Créneaux horaires de 8h à 18h avec différents types de cours
        $slots = [
            // Cours magistraux (CM) - 2h
            ['h' => 8,  'm' => 0,  'dur' => 120, 'type' => 'CM'], // 08:00–10:00
            ['h' => 10, 'm' => 15, 'dur' => 120, 'type' => 'CM'], // 10:15–12:15
            ['h' => 14, 'm' => 0,  'dur' => 120, 'type' => 'CM'], // 14:00–16:00
            ['h' => 16, 'm' => 15, 'dur' => 120, 'type' => 'CM'], // 16:15–18:15
            
            // Travaux dirigés (TD) - 1h30
            ['h' => 8,  'm' => 30, 'dur' => 90, 'type' => 'TD'],  // 08:30–10:00
            ['h' => 10, 'm' => 45, 'dur' => 90, 'type' => 'TD'],  // 10:45–12:15
            ['h' => 14, 'm' => 30, 'dur' => 90, 'type' => 'TD'],  // 14:30–16:00
            ['h' => 16, 'm' => 45, 'dur' => 90, 'type' => 'TD'],  // 16:45–18:15
            
            // Travaux pratiques (TP) - 3h
            ['h' => 8,  'm' => 0,  'dur' => 180, 'type' => 'TP'], // 08:00–11:00
            ['h' => 13, 'm' => 0,  'dur' => 180, 'type' => 'TP'], // 13:00–16:00
        ];

        // Jours de la semaine
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // helper pour choisir un cours compatible avec la classe (ManyToMany Course<->Classe), sinon fallback
        $pickCourseForClasse = function(Classe $classe) use ($courses): Course {
            $eligible = [];
            foreach ($courses as $c) {
                if (method_exists($c, 'getClasses') && $c->getClasses() && $c->getClasses()->contains($classe)) {
                    $eligible[] = $c;
                }
            }
            return $eligible ? $eligible[array_rand($eligible)] : $courses[array_rand($courses)];
        };

        // Créer des sessions pour 6 semaines (1.5 mois) - 2 semaines passées + 4 semaines futures
        $weeksToGenerate = 6;
        $startDate = new \DateTimeImmutable('first day of this month');
        $startDate = $startDate->modify('-2 weeks');
        
        for ($week = 0; $week < $weeksToGenerate; $week++) {
            $weekStart = $startDate->modify("+{$week} weeks");
            
            // Créer un emploi du temps cohérent pour chaque jour de la semaine
            foreach ($days as $dayName) {
                $day = $weekStart->modify($dayName)->setTime(0, 0);
                $usedSlotsForDay = [];
                
                // Assigner 2-3 créneaux par classe par jour (plus réaliste)
                foreach ($classes as $classe) {
                    $sessionsPerClass = $this->faker->numberBetween(2, 3); // 2-3 sessions par classe par jour
                    
                    for ($i = 0; $i < $sessionsPerClass; $i++) {
                        // Choisir un créneau libre pour cette classe
                        $availableSlots = array_filter($slots, function($slot) use ($usedSlotsForDay) {
                            $slotKey = $slot['h'] . ':' . $slot['m'] . '-' . $slot['type'];
                            return !in_array($slotKey, $usedSlotsForDay);
                        });
                        
                        if (!empty($availableSlots)) {
                            $slot = $availableSlots[array_rand($availableSlots)];
                            $slotKey = $slot['h'] . ':' . $slot['m'] . '-' . $slot['type'];
                            $usedSlotsForDay[] = $slotKey;
                            
                            $course = $pickCourseForClasse($classe);
                            $roomPrefix = $this->getRoomPrefixForSlotType($slot['type']);
                            $created[] = $this->persistSession($manager, $course, $classe, $professors, $day, $slot, $roomPrefix);
                        }
                    }
                }
            }
        }

        // Sessions spéciales pour le professeur de test (prof01@example.com) - plusieurs sessions sur le mois
        $testProfessor = null;
        foreach ($professors as $prof) {
            if (method_exists($prof, 'getUserId') && $prof->getUserId() && $prof->getUserId()->getEmail() === 'prof01@example.com') {
                $testProfessor = $prof;
                break;
            }
        }
        
        if ($testProfessor && count($classes) >= 2) {
            // Créer des sessions pour le professeur de test sur plusieurs jours du mois
            $testSlots = [
                ['h' => 9,  'm' => 0,  'dur' => 120], // 09:00–11:00
                ['h' => 15, 'm' => 0,  'dur' => 120], // 15:00–17:00
            ];
            
            // Créer 2 sessions par semaine pour le professeur de test
            for ($week = 0; $week < $weeksToGenerate; $week++) {
                $weekStart = $startDate->modify("+{$week} weeks");
                
                // Lundi et Mercredi pour le professeur de test
                $testDays = ['monday', 'wednesday'];
                foreach ($testDays as $dayName) {
                    $day = $weekStart->modify($dayName)->setTime(0, 0);
                    
                    foreach ($testSlots as $slot) {
                        $classe = $classes[array_rand($classes)];
                        $course = $pickCourseForClasse($classe);
                        $created[] = $this->persistSessionForProfessor($manager, $course, $classe, $testProfessor, $day, $slot, 'A');
                    }
                }
            }
        }

        return $created;
    }

    private function getRoomPrefixForSlotType(string $type): string
    {
        switch($type) {
            case 'CM':
                return 'A'; // Amphi
            case 'TD':
                return 'B'; // Salle de TD
            case 'TP':
                return 'C'; // Laboratoire
            default:
                return 'A';
        }
    }

    /** @return CourseSession */
    private function persistSession(
        ObjectManager $manager,
        Course $course,
        Classe $classe,
        array $professors,                 
        \DateTimeImmutable $date,
        array $slot,
        string $roomPrefix
    ) {
        $start = $date->setTime($slot['h'], $slot['m']);
        $end   = $start->modify('+' . $slot['dur'] . ' minutes');
        
        // Prendre un professeur aléatoire pour cette session
        $prof = $professors[array_rand($professors)];

        $s = new CourseSession();
        $s->setCourse($course);
        method_exists($s,'setClasse')    && $s->setClasse($classe);
        method_exists($s,'setProfessor') && $s->setProfessor($prof);
        method_exists($s,'setRoom')      && $s->setRoom($roomPrefix . random_int(100, 399));
        method_exists($s,'setStartAt')   && $s->setStartAt($start);
        method_exists($s,'setEndAt')     && $s->setEndAt($end);

        $manager->persist($s);
        return $s;
    }

    /** @return CourseSession */
    private function persistSessionForProfessor(
        ObjectManager $manager,
        Course $course,
        Classe $classe,
        Professor $professor,                 
        \DateTimeImmutable $date,
        array $slot,
        string $roomPrefix
    ): CourseSession {
        $start = $date->setTime($slot['h'], $slot['m']);
        $end   = $start->modify('+' . $slot['dur'] . ' minutes');

        $s = new CourseSession();
        $s->setCourse($course);
        method_exists($s,'setClasse')    && $s->setClasse($classe);
        method_exists($s,'setProfessor') && $s->setProfessor($professor);
        method_exists($s,'setRoom')      && $s->setRoom($roomPrefix . random_int(100, 399));
        method_exists($s,'setStartAt')   && $s->setStartAt($start);
        method_exists($s,'setEndAt')     && $s->setEndAt($end);

        $manager->persist($s);
        return $s;
    }

    /**
     * Crée des données de test spécifiques pour le composant ClassGrades
     * Un professeur de test avec des cours, classes et notes bien définis
     */
    private function createTestGradesForProfessor(ObjectManager $manager, array $students, array $courses, array $professors): void
    {
        // Trouver le premier professeur pour les tests
        $testProfessor = $professors[0] ?? null;
        if (!$testProfessor) {
            return;
        }

        // Récupérer les cours de ce professeur
        $professorCourses = [];
        foreach ($courses as $course) {
            if ($course->getProfessors()->contains($testProfessor)) {
                $professorCourses[] = $course;
            }
        }

        if (empty($professorCourses)) {
            return;
        }

        // Prendre les 2 premiers cours du professeur
        $testCourses = array_slice($professorCourses, 0, 2);
        
        // Récupérer les classes de ces cours
        $testClasses = [];
        foreach ($testCourses as $course) {
            foreach ($course->getClasses() as $class) {
                if (!in_array($class, $testClasses)) {
                    $testClasses[] = $class;
                }
            }
        }

        if (empty($testClasses)) {
            return;
        }

        // Prendre la première classe pour les tests
        $testClass = $testClasses[0];
        
        // Récupérer les étudiants de cette classe
        $testStudents = [];
        foreach ($students as $student) {
            if ($student->getClasse() === $testClass) {
                $testStudents[] = $student;
            }
        }

        if (empty($testStudents)) {
            return;
        }

        // Créer des notes de test pour chaque cours
        foreach ($testCourses as $course) {
            $this->createTestGradesForCourse($manager, $testStudents, $course, $testProfessor);
        }
    }

    /**
     * Crée des notes de test pour un cours spécifique
     */
    private function createTestGradesForCourse(ObjectManager $manager, array $students, Course $course, Professor $professor): void
    {
        // Types de contrôles de test
        $testControls = [
            ['title' => 'DS 1', 'divisor' => 20, 'date' => '-15 days'],
            ['title' => 'Contrôle', 'divisor' => 30, 'date' => '-10 days'],
            ['title' => 'Quiz 1', 'divisor' => 10, 'date' => '-5 days'],
            ['title' => 'TP 1', 'divisor' => 20, 'date' => '-3 days'],
            ['title' => 'Devoir 1', 'divisor' => 20, 'date' => '-1 day'],
        ];

        $baseDate = new \DateTimeImmutable('2025-01-01');

        foreach ($testControls as $control) {
            $createdAt = $baseDate->modify($control['date']);
            
            // Créer des notes pour tous les étudiants de la classe
            foreach ($students as $student) {
                // Générer une note réaliste
                $minGrade = max(0, $control['divisor'] * 0.4); // Minimum 40%
                $maxGrade = min($control['divisor'], $control['divisor'] * 0.9); // Maximum 90%
                $grade = random_int((int)$minGrade, (int)$maxGrade);

                $g = new Grade();
                $g->setStudent($student);
                $g->setCourse($course);
                $g->setTitle($control['title']);
                $g->setDividor($control['divisor']);
                $g->setGrade($grade);
                $g->setCreatedAt($createdAt);

                $manager->persist($g);
            }
        }
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create('fr_FR');
        $isLight = getenv('FIXTURE_MODE') === 'light';
        $this->initParameters($isLight);

        $levels     = $this->createLevels($manager);
        $categories = $this->createCategories($manager, $levels);
        $classes    = $this->createClasses($manager, $categories, $levels);
        $semesters  = $this->createSemesters($manager);

        $users       = $this->createUsers($manager, $classes, $semesters);   
        $professors  = $this->createProfessors($manager, $categories);
        $students    = $this->createStudents($manager, $classes, $semesters, $users);

        $manager->flush();

        // Réassigner les étudiants aux classes pour s'assurer qu'ils sont bien liés
        $this->assignStudentsToClasses($manager);
        $manager->flush();

        $courses     = $this->createCourses($manager, $categories, $levels, $semesters, $classes, $professors);

        $sessions = $this->seedCourseSessions($manager, $courses, $classes, $professors);
        $manager->flush();

        $this->createGrades($manager, $students, $courses);
        $this->createAbsences($manager, $students, $semesters);
        
        // Créer des données de test spécifiques pour le composant ClassGrades
        $this->createTestGradesForProfessor($manager, $students, $courses, $professors);
        
        // Créer les conversations de test
        $conversations = $this->createConversations($manager, $students, $professors);

        $manager->flush();
    }
}