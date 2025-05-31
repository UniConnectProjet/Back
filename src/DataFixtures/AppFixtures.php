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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $isLight = getenv('FIXTURE_MODE') === 'light';
        $nbCategories = $isLight ? 2 : 8;
        $nbNiveaux = $isLight ? 2 : 6;
        $classesParCombo = $isLight ? 2 : 5;
        $studentsParClasse = $isLight ? 10 : 30;

        $faker = Factory::create('fr_FR');

        // Niveaux d'études
        $niveaux = array_slice([
            'Bac+1', 'Bac+2', 'Bac+3', 'Bac+4', 'Bac+5', 'Doctorat'
        ], 0, $nbNiveaux);

        $levels = [];
        foreach ($niveaux as $nom) {
            $level = new Level();
            $level->setName($nom);
            $manager->persist($level);
            $levels[] = $level;
        }

        // Catégories
        $categoryNames = array_slice([
            'Informatique', 'Chimie', 'Génie Civil', 'Biologie', 'Mathématiques', 'Electronique', 'Physique', 'Gestion'
        ], 0, $nbCategories);

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

        // Certaines filières aient moins de niveaux
        /*foreach ($categoryNames as $catName) {
            $category = new Category();
            $category->setName($catName);

            if ($catName === 'Biologie') {
                $category->addLevelId($levels[0]); // Bac+1
                $category->addLevelId($levels[1]); // Bac+2
            } else {
                foreach ($levels as $level) {
                    $category->addLevelId($level);
                }
            }

            $manager->persist($category);
        }*/


        // Classes
        $classes = [];
        $classesByCategory = [];
        foreach ($categories as $category) {
            foreach ($levels as $level) {
                for ($i = 1; $i <= $classesParCombo; $i++) {
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

        // Semestres
        $semesters = [];
        for ($i = 1; $i <= 2; $i++) {
            $semester = new Semester();
            $semester->setName("Semestre $i");
            $semester->setStartDate($faker->dateTimeThisYear);
            $semester->setEndDate($faker->dateTimeThisYear);
            $manager->persist($semester);
            $semesters[] = $semester;
        }

        // Users + Students
        $students = [];
        $users = [];
        foreach ($classes as $classe) {
            for ($j = 0; $j < $studentsParClasse; $j++) {
                $user = new User();
                $user->setName($faker->firstName);
                $user->setLastname($faker->lastName);
                $user->setBirthday($faker->dateTimeBetween('-25 years', '-18 years'));
                $user->setEmail($faker->unique()->safeEmail);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
                $user->setRoles(['ROLE_USER']);
                $manager->persist($user);

                $student = new Student();
                $student->setUser($user);
                $student->setClasse($classe);
                foreach ($semesters as $semester) {
                    $student->addSemester($semester);
                }
                $manager->persist($student);
                $users[] = $user;
                $students[] = $student;
            }
        }

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
            if (!$category) continue;

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

        // Grades
        foreach ($students as $student) {
            foreach ($courses as $course) {
                if ($course->getClassId()->contains($student->getClasse())) {
                    $grade = new Grade();
                    $grade->setStudent($student);
                    $grade->setCourse($course);
                    $grade->setTitle($faker->word);
                    $grade->setGrade(mt_rand(10, 20));
                    $grade->setDividor(mt_rand(10, 20));
                    $manager->persist($grade);
                }
            }
        }

        // Absences
        foreach ($students as $student) {
            $absence = new Absence();
            $absence->setStudent($student);
            $absence->setStartedDate($faker->dateTimeThisYear);
            $absence->setEndedDate($faker->dateTimeThisYear);
            $justified = $faker->boolean;
            $absence->setJustified($justified);
            if ($justified) {
                $absence->setJustification($faker->sentence);
            }
            $absence->setSemester($semesters[array_rand($semesters)]);
            $manager->persist($absence);
        }

        $manager->flush();
    }
}