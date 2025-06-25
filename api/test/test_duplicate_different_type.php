<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST TIPO DIVERSO (dovrebbe permettere creazione)\n";
echo "=================================================\n\n";

// Login
$loginData = ['email' => 'paziente@test.it', 'password' => '12345678'];
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/auth/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($loginData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$loginResponse = curl_exec($ch);
$loginData = json_decode($loginResponse, true);
$token = $loginData['data']['access_token'] ?? null;

if (!$token) {
    echo "❌ ERRORE: Impossibile ottenere token\n";
    exit(1);
}

echo "✅ Token ottenuto\n\n";

// Test con tipo diverso (2 invece di 1)
echo "📋 Test richiesta tipo ID=2 (Relazione Terapeutica)\n";
$requestData = [
    'type_id' => 2, // TIPO DIVERSO
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio
    'reason' => 'Test tipo diverso - dovrebbe essere creata'
];

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);

echo "Status HTTP: $httpCode\n";

if ($data['success']) {
    echo "✅ SUCCESS\n";
    echo "   ID: " . $data['data']['id'] . "\n";
    echo "   Type ID: " . $data['data']['type_id'] . "\n";
    echo "   Request Type: " . $data['data']['request_type'] . "\n";
    echo "   Is duplicate: " . (isset($data['data']['is_duplicate']) && $data['data']['is_duplicate'] ? 'YES' : 'NO') . "\n";
    
    if ($httpCode === 201) {
        echo "   🆕 NUOVA RICHIESTA CREATA (Status 201)\n";
    } elseif ($httpCode === 200) {
        echo "   🔄 RICHIESTA DUPLICATA (Status 200)\n";
        if (isset($data['data']['duplicate_message'])) {
            echo "   Messaggio: " . $data['data']['duplicate_message'] . "\n";
        }
    }
} else {
    echo "❌ FAILED: " . ($data['error'] ?? 'Errore sconosciuto') . "\n";
}

curl_close($ch);
echo "\n🎯 TEST COMPLETATO!\n";
?> 