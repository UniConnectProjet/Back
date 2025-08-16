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

    private function createUsers(ObjectManager $manager, array $classes, array $semesters): array {
        $users = [];
            for ($j = 0; $j < $this->studentsParClasse; $j++) {
                $user = new User();
                $user->setName($this->faker->firstName);
                $user->setLastname($this->faker->lastName);
                $user->setBirthday($this->faker->dateTimeBetween('-25 years', '-18 years'));
                $user->setEmail($this->faker->unique()->safeEmail);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
                $user->setRoles(['ROLE_USER']);
                $manager->persist($user);
                $users[] = $user;
            }
        return $users;
    }

    private function createStudents(ObjectManager $manager, array $classes, array $semesters, array $users): array {

        $student = new Student();
        foreach($users as $user){
            foreach ($classes as $classe) {
                $student->setUser($user);
                $student->setClasse($classe);
                foreach ($semesters as $semester) {
                    $student->addSemester($semester);
                }
            }
                $manager->persist($student);
                $students[] = $student;
        }            
        return $users;
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

    private function createGrades(ObjectManager $manager, array $students, array $courses): void { 
        // Grades
        foreach ($students as $student) {
            foreach ($courses as $course) {
                if ($course->getClassId()->contains($student->getClasse())) {
                    $grade = new Grade();
                    $grade->setStudent($student);
                    $grade->setCourse($course);
                    $grade->setTitle($this->faker->word);
                    $grade->setGrade(mt_rand(10, 20));
                    $grade->setDividor(mt_rand(10, 20));
                    $manager->persist($grade);
                }
            }
        }
     }

    private function createAbsences(ObjectManager $manager, array $students, array $semesters): void { 
        // Absences
        foreach ($students as $student) {
            $absence = new Absence();
            $absence->setStudent($student);
            $absence->setStartedDate($this->faker->dateTimeThisYear);
            $absence->setEndedDate($this->faker->dateTimeThisYear);
            $justified = $this->faker->boolean;
            $absence->setJustified($justified);
            if ($justified) {
                $absence->setJustification($this->faker->sentence);
            }
            $absence->setSemester($semesters[array_rand($semesters)]);
            $manager->persist($absence);
        }
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
        $this->createStudents($manager, $classes, $semesters, $users);
        $courses = $this->createCourses($manager, $categories, $levels, $semesters, $classes);
        $this->createGrades($manager, $users, $courses);
        $this->createAbsences($manager, $users, $semesters);

        $manager->flush();
    }
}