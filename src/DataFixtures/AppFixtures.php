<?php

namespace App\DataFixtures;

use App\Entity\Absence;
use App\Entity\Category;
use App\Entity\Classe;
use App\Entity\Course;
use App\Entity\CourseUnit;
use App\Entity\Grade;
use App\Entity\Level;
use App\Entity\Semester;
use App\Entity\Student;
use App\Entity\CourseSession;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private int $nbCategories;
    private int $nbNiveaux;
    private int $classesParCombo;
    private int $studentsParClasse;
    private \Faker\Generator $faker;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    private function initParameters(bool $isLight): void
    {
        $this->nbCategories = $isLight ? 2 : 8;
        $this->nbNiveaux = $isLight ? 2 : 6;
        $this->classesParCombo = $isLight ? 2 : 5;
        $this->studentsParClasse = $isLight ? 10 : 30;
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
        return $classes;
    }

    private function createSemesters(ObjectManager $manager): array {
        // Semestres
        $semesters = [];
        for ($i = 1; $i <= 2; $i++) {
            $semester = new Semester();
            $semester->setName("Semestre $i");
            $semester->setStartDate($this->faker->dateTimeThisYear);
            $semester->setEndDate($this->faker->dateTimeThisYear);
            $manager->persist($semester);
            $semesters[] = $semester;
        }
        return $semesters;
    }

    private function createUsers(ObjectManager $manager, array $classes, array $semesters): array
    {
        $users = [];
        for ($j = 0; $j < $this->studentsParClasse; $j++) {
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

    private function createStudents(ObjectManager $manager, array $classes, array $semesters, array $users): array
    {
        $students = [];
        foreach ($users as $user) {
            $student = new Student();
            $student->setUser($user);

            $classe = $classes[array_rand($classes)];
            $student->setClasse($classe);

            foreach ($semesters as $semester) {
                $student->addSemester($semester);
            }

            $manager->persist($student);
            $students[] = $student;
        }
        return $students;
    }
    
    private function createCourses(ObjectManager $manager, array $categories, array $levels, array $semesters, array $classes): array { 
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

        foreach ($courseParCategorie as $categorieNom => $ues) {
            $category = $categoryByName[$categorieNom] ?? null;
            if (!$category) {
                continue;
            }

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

                    if (isset($classesByCategory[$categorieNom])) {
                        foreach ($classesByCategory[$categorieNom] as $classe) {
                            $course->addClassId($classe);
                        }
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
            $toPick = array_slice($toPick, 0, min(random_int(3, 6), count($toPick)));

            foreach ($toPick as $course) {
                $dividorChoices = [10, 16, 20, 20, 20];
                $dividor = $dividorChoices[array_rand($dividorChoices)];
                $grade   = random_int(0, $dividor);

                $g = new Grade();
                $g->setStudent($student);
                $g->setCourse($course);
                $g->setTitle($this->faker->randomElement(['TP', 'DS', 'Quiz', 'Projet']).' '.$this->faker->numberBetween(1, 3));
                $g->setDividor($dividor);
                $g->setGrade($grade);

                $manager->persist($g);
            }
        }
    }


    private function createAbsences(ObjectManager $manager, array $students, array $semesters): void
    {
        // === Config souple ===
        $tzName      = \date_default_timezone_get();
        $minHour     = 8;    
        $maxHour     = 19;   
        $durations   = [60, 120]; 
        $minSlots    = 1;     
        $maxSlots    = 3;     
        $minuteStep  = 60;   

        $possibleMinutes = ($minuteStep >= 60)
            ? [0]
            : range(0, 59, max(1, min(59, $minuteStep)));

        foreach ($students as $student) {
            $day = $this->faker->dateTimeThisYear('now', $tzName);
            $day->setTimezone(new \DateTimeZone($tzName));
            $baseDay = (clone $day)->setTime(0, 0, 0);
            $targetSlots = $this->faker->numberBetween($minSlots, $maxSlots);
            $intervals = [];

            $attempts = 0;
            while (count($intervals) < $targetSlots && $attempts < 32) {
                $attempts++;

                $duration = $this->faker->randomElement($durations);
                $lastStartHour = min($maxHour, 23 - intdiv($duration, 60));
                if ($lastStartHour < $minHour) {
                    break; // plage impossible avec cette durée
                }

                $startHour   = $this->faker->numberBetween($minHour, $lastStartHour);
                $startMinute = $this->faker->randomElement($possibleMinutes);

                $start = (clone $baseDay)->setTime($startHour, $startMinute, 0);
                $end   = (clone $start)->modify("+{$duration} minutes");

                if ($end->format('Y-m-d') !== $baseDay->format('Y-m-d')) {
                    continue;
                }

                $overlap = false;
                foreach ($intervals as [$s, $e]) {
                    if ($start < $e && $end > $s) { $overlap = true; break; }
                }
                if ($overlap) continue;

                $intervals[] = [$start, $end];
            }

            usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

            foreach ($intervals as [$start, $end]) {
                $absence = new Absence();
                $absence->setStudent($student);
                $absence->setStartedDate($start);
                $absence->setEndedDate($end);

                $justified = $this->faker->boolean();
                $absence->setJustified($justified);
                if ($justified) {
                    $absence->setJustification($this->faker->sentence());
                }

                if (!empty($semesters)) {
                    $semester = null;
                    foreach ($semesters as $s) {
                        if (method_exists($s, 'getStartDate') && method_exists($s, 'getEndDate')) {
                            $sStart = $s->getStartDate();
                            $sEnd   = $s->getEndDate();
                            if ($sStart && $sEnd && $start >= $sStart && $end <= $sEnd) { $semester = $s; break; }
                        }
                    }
                    $absence->setSemester($semester ?? $semesters[array_rand($semesters)]);
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
        $classeRepo   = $manager->getRepository(Classe::class);
        $studentRepo  = $manager->getRepository(Student::class);
        $userRepo     = $manager->getRepository(User::class);

        /** @var Classe[] $classes */
        $classes = $classeRepo->findAll();
        if (!$classes) return;

        /** @var User[] $users */
        $users = $userRepo->findAll();
        $studentsUsers = array_values(array_filter($users, fn(User $u) => $this->hasRole($u, 'ROLE_STUDENT')));

        $i = 0;
        foreach ($studentsUsers as $user) {
            // réutilise Student existant si présent
            $student = $studentRepo->findOneBy(['user' => $user]) ?? new Student();
            $student->setUser($user);

            // round-robin sur les classes
            $classe = $classes[$i % count($classes)];
            if (method_exists($student, 'setClasse')) {
                $student->setClasse($classe);
            }
            $manager->persist($student);
            $i++;
        }
    }

    private function seedCourseSessions(ObjectManager $manager): void
    {
        $courseRepo = $manager->getRepository(Course::class);
        $classeRepo = $manager->getRepository(Classe::class);
        $userRepo   = $manager->getRepository(User::class);

        /** @var Course[] $courses */
        $courses = $courseRepo->findAll();
        /** @var Classe[] $classes */
        $classes = $classeRepo->findAll();
        /** @var User[] $users */
        $users   = $userRepo->findAll();

        if (!$courses || !$classes) return;

        // profs si dispo, sinon n’importe quel user
        $professors = array_values(array_filter($users, fn(User $u) => $this->hasRole($u, 'ROLE_PROF')));
        if (!$professors) $professors = $users;

        // créneaux types (durée en minutes)
        $slots = [
            ['h' => 8,  'm' => 0,  'dur' => 120], // 08:00–10:00
            ['h' => 10, 'm' => 15, 'dur' => 120], // 10:15–12:15
            ['h' => 14, 'm' => 0,  'dur' => 120], // 14:00–16:00
            ['h' => 16, 'm' => 15, 'dur' => 120], // 16:15–18:15
        ];

        // helper pour choisir un cours compatible avec la classe (ManyToMany Course<->Classe), sinon fallback
        $pickCourseForClasse = function(Classe $classe) use ($courses): Course {
            $eligible = [];
            foreach ($courses as $c) {
                if (method_exists($c, 'getClasses') && $c->getClasses()?->contains($classe)) {
                    $eligible[] = $c;
                }
            }
            return ($eligible ? $eligible[array_rand($eligible)] : $courses[array_rand($courses)]);
        };

        // 2 séances demain pour 2 classes (test "NextDayCourses")
        $tomorrow = (new \DateTimeImmutable('tomorrow'))->setTime(0, 0);
        foreach (array_slice($classes, 0, min(2, count($classes))) as $classe) {
            foreach (array_slice($slots, 0, 2) as $slot) {
                $this->persistSession($manager, $pickCourseForClasse($classe), $classe, $professors, $tomorrow, $slot, 'B');
            }
        }

        // semaine courante (lun→ven) : 2 séances / jour / classe (pour l’EDT hebdo)
        $monday = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
        for ($d = 0; $d < 5; $d++) {
            $day = $monday->modify("+$d day");
            foreach ($classes as $classe) {
                foreach (array_slice($slots, 0, 2) as $slot) {
                    $this->persistSession($manager, $pickCourseForClasse($classe), $classe, $professors, $day, $slot, 'A');
                }
            }
        }
    }

    private function persistSession(
        ObjectManager $manager,
        Course $course,
        Classe $classe,
        array $professors,
        \DateTimeImmutable $date,
        array $slot,
        string $roomPrefix
    ): void {
        $start = $date->setTime($slot['h'], $slot['m']);
        $end   = $start->modify('+' . $slot['dur'] . ' minutes');
        $prof  = $professors ? $professors[array_rand($professors)] : null;

        $s = new CourseSession();
        $s->setCourse($course);

        // Classe : ManyToOne (setClasse) OU ManyToMany (addClasse) selon ton mapping
        if (method_exists($s, 'setClasse')) {
            $s->setClasse($classe);
        } elseif (method_exists($s, 'addClasse')) {
            $s->addClasse($classe);
        }

        if ($prof && method_exists($s, 'setProfessor')) $s->setProfessor($prof);
        if (method_exists($s, 'setRoom')) $s->setRoom($roomPrefix . random_int(100, 399));
        $s->setStartAt($start);
        $s->setEndAt($end);

        $manager->persist($s);
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create('fr_FR');
        $isLight = getenv('FIXTURE_MODE') === 'light';

        $this->initParameters($isLight);

        $levels = $this->createLevels($manager);
        $categories = $this->createCategories($manager, $levels);
        $classes = $this->createClasses($manager, $categories, $levels);
        $semesters = $this->createSemesters($manager);
        $users = $this->createUsers($manager, $classes, $semesters);
        $students = $this->createStudents($manager, $classes, $semesters, $users);
        $courses = $this->createCourses($manager, $categories, $levels, $semesters, $classes);
        $this->createGrades($manager, $students, $courses);
        $this->createAbsences($manager, $students, $semesters);
        $this->assignStudentsToClasses($manager);
        $this->seedCourseSessions($manager); 

        $manager->flush();
    }
}