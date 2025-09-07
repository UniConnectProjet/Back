<?php

require_once 'vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

// Configuration Doctrine
$config = Setup::createAnnotationMetadataConfiguration(
    [__DIR__ . '/src/Entity'],
    true,
    null,
    null,
    false
);

// Configuration de la base de données
$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'pfe_db',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];

$entityManager = EntityManager::create($connectionParams, $config);

// Récupérer les notes pour le cours 14
$grades = $entityManager->getRepository('App\Entity\Grade')->findBy(['course' => 14]);

echo "=== NOTES POUR LE COURS 14 ===\n";
echo "Nombre de notes trouvées: " . count($grades) . "\n\n";

foreach ($grades as $grade) {
    echo "ID: " . $grade->getId() . "\n";
    echo "Titre: " . $grade->getTitle() . "\n";
    echo "Note: " . $grade->getGrade() . "/" . $grade->getDividor() . "\n";
    echo "Étudiant ID: " . ($grade->getStudent() ? $grade->getStudent()->getId() : 'NULL') . "\n";
    echo "Cours ID: " . ($grade->getCourse() ? $grade->getCourse()->getId() : 'NULL') . "\n";
    echo "Date: " . ($grade->getCreatedAt() ? $grade->getCreatedAt()->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "---\n";
}

// Récupérer les étudiants de la classe 4
$students = $entityManager->getRepository('App\Entity\Student')->findBy(['classe' => 4]);

echo "\n=== ÉTUDIANTS DE LA CLASSE 4 ===\n";
echo "Nombre d'étudiants trouvés: " . count($students) . "\n\n";

foreach ($students as $student) {
    echo "ID: " . $student->getId() . "\n";
    echo "Nom: " . ($student->getUser() ? $student->getUser()->getName() . ' ' . $student->getUser()->getLastname() : 'NULL') . "\n";
    echo "---\n";
}
