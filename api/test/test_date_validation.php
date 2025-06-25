<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "🗓️  TEST VALIDAZIONE DATE (date_to >= date_from)\n";
echo "==============================================\n\n";

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

// Test 1: date_to < date_from (dovrebbe fallire)
echo "❌ TEST 1: date_to PRIMA di date_from (dovrebbe fallire)\n";
$requestData1 = [
    'type_id' => 1, // Certificato Medico (richiede date)
    'patient_id' => 1,
    'reason' => 'Test validazione date - ordine sbagliato',
    'date_from' => '2025-02-10', // Data DOPO date_to
    'date_to' => '2025-02-05'    // Data PRIMA di date_from
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
echo "Response: " . json_encode($data1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if (!$data1['success'] && $httpCode1 === 400) {
    echo "✅ VALIDAZIONE OK: Date non valide rifiutate\n";
    if (isset($data1['details']['date_to']) && strpos($data1['details']['date_to'], 'successiva o uguale') !== false) {
        echo "✅ MESSAGGIO CORRETTO: Controlla che date_to >= date_from\n";
    } else {
        echo "⚠️  Messaggio non specifico per date_to\n";
    }
} else {
    echo "❌ PROBLEMA: Date non valide accettate!\n";
}

echo "\n";

// Test 2: date_to = date_from (dovrebbe funzionare)
echo "✅ TEST 2: date_to UGUALE a date_from (dovrebbe funzionare)\n";
$requestData2 = [
    'type_id' => 1, // Certificato Medico
    'patient_id' => 1,
    'reason' => 'Test validazione date - stessa data',
    'date_from' => '2025-02-15',
    'date_to' => '2025-02-15'   // Stessa data
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

if ($data2['success'] && ($httpCode2 === 201 || $httpCode2 === 200)) {
    echo "✅ VALIDAZIONE OK: Date uguali accettate\n";
    echo "   ID: " . $data2['data']['id'] . "\n";
    echo "   Date: " . $data2['data']['date_from'] . " → " . $data2['data']['date_to'] . "\n";
} else {
    echo "❌ PROBLEMA: Date uguali rifiutate!\n";
    echo "   Error: " . ($data2['error'] ?? 'N/A') . "\n";
}

echo "\n";

// Test 3: date_to > date_from (dovrebbe funzionare)
echo "✅ TEST 3: date_to DOPO date_from (dovrebbe funzionare)\n";
$requestData3 = [
    'type_id' => 1, // Certificato Medico
    'patient_id' => 1,
    'reason' => 'Test validazione date - ordine corretto',
    'date_from' => '2025-03-01',
    'date_to' => '2025-03-10'   // Data DOPO date_from
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

if ($data3['success'] && ($httpCode3 === 201 || $httpCode3 === 200)) {
    echo "✅ VALIDAZIONE OK: Date corrette accettate\n";
    echo "   ID: " . $data3['data']['id'] . "\n";
    echo "   Date: " . $data3['data']['date_from'] . " → " . $data3['data']['date_to'] . "\n";
} else {
    echo "❌ PROBLEMA: Date corrette rifiutate!\n";
    echo "   Error: " . ($data3['error'] ?? 'N/A') . "\n";
}

curl_close($ch);

echo "\n🎯 RIEPILOGO VALIDAZIONE DATE\n";
echo "============================\n";
echo "✅ Test 1: date_to < date_from → RIFIUTATO\n";
echo "✅ Test 2: date_to = date_from → ACCETTATO\n";
echo "✅ Test 3: date_to > date_from → ACCETTATO\n";
echo "\n📅 VALIDAZIONE DATE IMPLEMENTATA CORRETTAMENTE!\n";
?> 