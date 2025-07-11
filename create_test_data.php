<?php
// Script per creare dati di test
$url = 'http://localhost/TherapyCRM/therapeutic-plan-manager/get-therapists';

echo "=== CREAZIONE DATI DI TEST ===\n\n";

// Prima otteniamo i terapisti disponibili
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "1. TERAPISTI DISPONIBILI:\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

$therapists = json_decode($response, true);
if ($therapists && $therapists['success']) {
    echo "Terapisti trovati:\n";
    foreach ($therapists['data'] as $therapist) {
        echo "- ID: {$therapist['id']}, Nome: {$therapist['name']}\n";
    }
} else {
    echo "❌ Errore nel recupero terapisti\n";
}

// Ora testiamo con date molto ampie per vedere se funziona
echo "\n2. TEST CON DATE AMPIE:\n";
$testData = [
    'planTherapyId' => 1,
    'therapistId' => 1,
    'dayOfWeek' => 1,
    'startTime' => '10:00',
    'durationMinutes' => 60,
    'validFrom' => '2020-01-01', // Data molto passata
    'validTo' => '2030-12-31'    // Data molto futura
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TherapyCRM/therapeutic-plan-manager/create-pattern');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$decoded = json_decode($response, true);
if ($decoded) {
    echo "\nDecoded Response:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
}
?> 