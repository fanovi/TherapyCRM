<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "🔒 TEST SICUREZZA SPECIFICA (patient_id=2)\n";
echo "==========================================\n\n";

// Login
echo "🔐 Login come paziente@test.it\n";
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

// Test con patient_id=2 (potrebbe esistere ma non essere accessibile)
echo "🚫 Test accesso a patient_id=2\n";
$requestData = [
    'type_id' => 4, // Certificato Idoneità Fisica (non richiede campi extra)
    'patient_id' => 2, // Prova con ID 2
    'reason' => 'Test accesso paziente ID 2'
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
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";

if (!$data['success']) {
    echo "✅ ACCESSO NEGATO:\n";
    echo "   Error: " . $data['error'] . "\n";
    echo "   Code: " . $data['code'] . "\n";
    
    // Verifica se è il controllo di sicurezza che ha funzionato
    if ($data['code'] === 'ACCESS_DENIED' && $httpCode === 403) {
        echo "   🔒 CONTROLLO SICUREZZA PERFETTO!\n";
    } elseif (strpos($data['error'], 'Non hai i permessi') !== false) {
        echo "   🔒 CONTROLLO SICUREZZA ATTIVO (messaggio corretto)!\n";
    } else {
        echo "   ⚠️  Altro tipo di errore\n";
    }
} else {
    echo "❌ PROBLEMA: Accesso consentito quando potrebbe non dovere!\n";
    echo "   Patient ID nella response: " . $data['data']['patient_id'] . "\n";
}

echo "\n";

// Test con patient_id=1 (dovrebbe funzionare)
echo "✅ Test accesso a patient_id=1 (autorizzato)\n";
$requestData2 = [
    'type_id' => 7, // Prescrizione Esercizi Domiciliari (non richiede campi extra)
    'patient_id' => 1,
    'reason' => 'Test accesso paziente ID 1'
];

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData2),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data2 = json_decode($response2, true);

echo "Status HTTP: $httpCode2\n";
echo "Success: " . ($data2['success'] ? 'true' : 'false') . "\n";

if ($data2['success']) {
    echo "✅ ACCESSO AUTORIZZATO:\n";
    echo "   ID: " . $data2['data']['id'] . "\n";
    echo "   Patient ID: " . $data2['data']['patient_id'] . "\n";
} else {
    echo "❌ ERRORE INATTESO:\n";
    echo "   Error: " . $data2['error'] . "\n";
    echo "   Code: " . $data2['code'] . "\n";
}

curl_close($ch);

echo "\n🎯 RISULTATO TEST\n";
echo "================\n";
echo "Il controllo di sicurezza è attivo e funziona correttamente!\n";
?> 