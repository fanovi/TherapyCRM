<?php
// Test script per verificare la creazione di pattern
$url = 'http://localhost/TherapyCRM/therapeutic-plan-manager/create-pattern';

// Dati di test - usa date realistiche per il 2025
$data = [
    'planTherapyId' => 1,  // Prova con ID 1
    'therapistId' => 1,    // Prova con ID 1
    'dayOfWeek' => 1,      // Lunedì
    'startTime' => '10:00',
    'durationMinutes' => 60,
    'validFrom' => '2025-01-20', // Data nel 2025
    'validTo' => '2025-03-20'    // Due mesi dopo
];

echo "Testando creazione pattern con dati:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";

// Prova anche a decodificare la risposta JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "\nDecoded Response:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    
    // Se c'è stato successo, mostra i dettagli
    if (isset($decoded['success']) && $decoded['success']) {
        echo "\n✅ SUCCESSO! Pattern creato con:\n";
        echo "- Appuntamenti creati: " . ($decoded['appointmentsCreated'] ?? 'N/A') . "\n";
        echo "- Conflitti: " . (count($decoded['conflicts'] ?? []) > 0 ? count($decoded['conflicts']) : 'Nessuno') . "\n";
        echo "- Pattern ID: " . ($decoded['data']['patternId'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ ERRORE: " . ($decoded['error'] ?? 'Errore sconosciuto') . "\n";
    }
}
?> 