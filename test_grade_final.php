<?php

// Test de création de note avec des données complètes
$url = 'http://localhost:8000/api/grade/student/4';
$data = [
    'grade' => 15.5,
    'dividor' => 20.0,
    'title' => 'Test Note Finale',
    'course' => 1,
    'studentId' => 4
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Status Code: " . http_response_code() . "\n";
echo "Response: " . $result . "\n";

if (http_response_code() === 200 || http_response_code() === 201) {
    echo "✅ Test réussi !\n";
} else {
    echo "❌ Test échoué\n";
}
