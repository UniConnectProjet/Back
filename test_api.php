<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

// Test de l'API des notes du professeur
echo "=== Test de l'API des notes du professeur ===\n\n";

// 1. Test de l'endpoint overview
echo "1. Test de /api/prof/grades/overview\n";
try {
    $response = $client->request('GET', 'http://localhost:8000/api/prof/grades/overview', [
        'headers' => [
            'Authorization' => 'Bearer test-token', // Token de test
            'Content-Type' => 'application/json'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";
    
    if ($statusCode === 200) {
        $data = $response->toArray();
        echo "Réponse reçue avec succès!\n";
        echo "Nombre de cours: " . count($data['byCourse'] ?? []) . "\n";
        if (!empty($data['byCourse'])) {
            $firstCourse = $data['byCourse'][0];
            echo "Premier cours: " . $firstCourse['courseTitle'] . "\n";
            echo "Nombre de classes: " . count($firstCourse['classes'] ?? []) . "\n";
        }
    } else {
        echo "Erreur: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test de l'endpoint des cours du professeur
echo "2. Test de /api/prof/courses\n";
try {
    $response = $client->request('GET', 'http://localhost:8000/api/prof/courses', [
        'headers' => [
            'Authorization' => 'Bearer test-token',
            'Content-Type' => 'application/json'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";
    
    if ($statusCode === 200) {
        $data = $response->toArray();
        echo "Réponse reçue avec succès!\n";
        echo "Nombre de cours: " . count($data) . "\n";
        if (!empty($data)) {
            $firstCourse = $data[0];
            echo "Premier cours: " . $firstCourse['name'] . "\n";
        }
    } else {
        echo "Erreur: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test de l'endpoint des classes du professeur
echo "3. Test de /api/prof/classes\n";
try {
    $response = $client->request('GET', 'http://localhost:8000/api/prof/classes', [
        'headers' => [
            'Authorization' => 'Bearer test-token',
            'Content-Type' => 'application/json'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";
    
    if ($statusCode === 200) {
        $data = $response->toArray();
        echo "Réponse reçue avec succès!\n";
        echo "Nombre de classes: " . count($data) . "\n";
        if (!empty($data)) {
            $firstClass = $data[0];
            echo "Première classe: " . $firstClass['name'] . "\n";
        }
    } else {
        echo "Erreur: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n=== Fin des tests ===\n";
