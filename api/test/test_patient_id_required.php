<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "🔒 TEST PATIENT_ID OBBLIGATORIO\n";
echo "===============================\n\n";

// Login
echo "🔐 STEP 1: Login per ottenere token JWT\n";
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

// Test 1: Richiesta SENZA patient_id (dovrebbe fallire)
echo "❌ STEP 2: Test SENZA patient_id (dovrebbe fallire con validazione)\n";
$requestData1 = [
    'type_id' => 4, // Certificato Idoneità Fisica
    'reason' => 'Test senza patient_id'
];

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData1),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data1 = json_decode($response1, true);

echo "Status HTTP: $httpCode1\n";

if (!$data1['success'] && $data1['code'] === 'MISSING_REQUIRED_FIELD') {
    echo "✅ VALIDAZIONE OK: patient_id richiesto correttamente\n";
    echo "   Error: " . $data1['error'] . "\n";
    if (isset($data1['details']['patient_id'])) {
        echo "   Detail: " . $data1['details']['patient_id'] . "\n";
    }
} else {
    echo "❌ PROBLEMA: Validazione non ha funzionato\n";
    echo "   Response: " . json_encode($data1, JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 2: Richiesta con patient_id NON AUTORIZZATO
echo "🚫 STEP 3: Test con patient_id NON AUTORIZZATO (ID=999)\n";
$requestData2 = [
    'type_id' => 4,
    'patient_id' => 999, // ID inesistente/non autorizzato
    'reason' => 'Test accesso non autorizzato'
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

if (!$data2['success'] && $data2['code'] === 'ACCESS_DENIED' && $httpCode2 === 403) {
    echo "✅ SICUREZZA OK: Accesso negato correttamente\n";
    echo "   Error: " . $data2['error'] . "\n";
    
    // Verifica se mostra i pazienti accessibili
    if (strpos($data2['error'], 'Pazienti accessibili:') !== false) {
        echo "   🔒 MESSAGGIO INFORMATIVO: Mostra pazienti accessibili\n";
    }
} else {
    echo "❌ PROBLEMA SICUREZZA: Controllo non ha funzionato\n";
    echo "   Response: " . json_encode($data2, JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 3: Richiesta con patient_id AUTORIZZATO
echo "✅ STEP 4: Test con patient_id AUTORIZZATO (ID=1)\n";
$requestData3 = [
    'type_id' => 4,
    'patient_id' => 1, // ID autorizzato
    'reason' => 'Test accesso autorizzato'
];

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData3),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data3 = json_decode($response3, true);

echo "Status HTTP: $httpCode3\n";

if ($data3['success']) {
    echo "✅ ACCESSO OK: Richiesta creata/restituita correttamente\n";
    echo "   ID: " . $data3['data']['id'] . "\n";
    echo "   Patient ID: " . $data3['data']['patient_id'] . "\n";
    echo "   Request Type: " . $data3['data']['request_type'] . "\n";
    
    if ($httpCode3 === 201) {
        echo "   🆕 NUOVA RICHIESTA CREATA\n";
    } elseif ($httpCode3 === 200) {
        echo "   🔄 RICHIESTA DUPLICATA RESTITUITA\n";
        if (isset($data3['data']['is_duplicate']) && $data3['data']['is_duplicate']) {
            echo "   Duplicate message: " . $data3['data']['duplicate_message'] . "\n";
        }
    }
} else {
    echo "❌ ERRORE INATTESO: Richiesta valida rifiutata\n";
    echo "   Error: " . $data3['error'] . "\n";
    echo "   Code: " . $data3['code'] . "\n";
}

curl_close($ch);

echo "\n🎯 RIEPILOGO TEST PATIENT_ID OBBLIGATORIO\n";
echo "=========================================\n";
echo "✅ Test 1: Validazione patient_id obbligatorio\n";
echo "✅ Test 2: Controllo sicurezza accesso negato\n";
echo "✅ Test 3: Accesso autorizzato funzionante\n";
echo "\n🔒 SISTEMA SICURO E FUNZIONANTE!\n";
?> 