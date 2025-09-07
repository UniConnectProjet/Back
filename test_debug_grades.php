<?php

// Test de débogage des notes
$url = 'http://localhost:8000/api/prof/test-grades/14/4';

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'GET'
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Status Code: " . http_response_code() . "\n";
echo "Response: " . $result . "\n";

if (http_response_code() === 200) {
    echo "✅ Test réussi !\n";
    $data = json_decode($result, true);
    if (isset($data['grades'])) {
        echo "Nombre total de notes pour le cours 14: " . $data['count'] . "\n";
        echo "Nombre de notes pour la classe 4: " . $data['filteredCount'] . "\n";
        
        foreach ($data['grades'] as $grade) {
            echo "- " . $grade['title'] . " : " . $grade['grade'] . "/" . $grade['divisor'] . " (étudiant: " . $grade['studentId'] . ", classe: " . $grade['studentClassId'] . ")\n";
        }
    }
} else {
    echo "❌ Test échoué\n";
}
