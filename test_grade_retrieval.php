<?php

// Test de récupération des notes pour un cours et une classe
$url = 'http://localhost:8000/api/prof/courses/14/classes/4/grades';

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
    if (isset($data['controls'])) {
        echo "Nombre de contrôles trouvés: " . count($data['controls']) . "\n";
        foreach ($data['controls'] as $control) {
            echo "- " . $control['title'] . " (" . count($control['grades']) . " notes)\n";
        }
    }
} else {
    echo "❌ Test échoué\n";
}
