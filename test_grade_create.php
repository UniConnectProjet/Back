<?php

// Test de création de note
$url = 'http://localhost:8000/api/grade/student/4';
$data = [
    'grade' => 15.5,
    'dividor' => 20.0,
    'title' => 'Test Note',
    'course' => 1
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
