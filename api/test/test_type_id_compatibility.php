<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "�� TEST COMPATIBILITÀ request_type_id (FORMATO STANDARD)\n";
echo "=======================================================\n\n";

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

// Test del tuo body originale con il campo corretto
echo "📋 TEST: Body originale corretto\n";
echo "--------------------------------\n";

$requestData = [
    'request_type_id' => 3,  // ✅ CAMPO CORRETTO
    'patient_id' => 1,       // ✅ CAMPO OBBLIGATORIO AGGIUNTO
    'therapeutic_plan_id' => null,
    'therapy_id' => null,
    'notes' => 'Richiesta specialistica'
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
    echo "✅ SUCCESS - Request creata correttamente!\n";
    echo "   ID: " . $data['data']['id'] . "\n";
    echo "   Request Type ID: " . $data['data']['request_type_id'] . "\n";
    echo "   Request Type: " . $data['data']['request_type'] . "\n";
    echo "   Patient ID: " . $data['data']['patient_id'] . "\n";
    echo "   Notes: " . ($data['data']['notes'] ?? 'null') . "\n";
    
    if (isset($data['data']['is_duplicate']) && $data['data']['is_duplicate']) {
        echo "   🔄 RICHIESTA DUPLICATA (restituisce esistente)\n";
        echo "   Messaggio: " . $data['data']['duplicate_message'] . "\n";
    } else {
        echo "   🆕 NUOVA RICHIESTA CREATA\n";
        echo "   Messaggio: " . $data['message'] . "\n";
    }
} else {
    echo "❌ FAILED: " . ($data['error'] ?? 'Errore sconosciuto') . "\n";
    echo "   Code: " . ($data['code'] ?? 'N/A') . "\n";
    if (isset($data['details'])) {
        echo "   Details: " . json_encode($data['details'], JSON_PRETTY_PRINT) . "\n";
    }
}

curl_close($ch);

echo "\n🎯 SOLUZIONE AL TUO PROBLEMA:\n";
echo "=============================\n";
echo "✅ Usa 'request_type_id' invece di 'type_id'\n";
echo "✅ Aggiungi sempre 'patient_id' come campo obbligatorio\n";
echo "✅ Body corretto per la tua richiesta:\n";
echo json_encode([
    'request_type_id' => 3,
    'patient_id' => 1,
    'therapeutic_plan_id' => null,
    'therapy_id' => null,
    'notes' => 'Richiesta specialistica'
], JSON_PRETTY_PRINT) . "\n"; 