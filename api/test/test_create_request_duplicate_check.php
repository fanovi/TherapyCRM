<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST CONTROLLO DUPLICATI RICHIESTE\n";
echo "=====================================\n\n";

// Step 1: Login per token
echo "🔐 STEP 1: Login per ottenere token JWT\n";
$loginData = [
    'email' => 'paziente@test.it',
    'password' => '12345678'
];

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
    echo "Response: $loginResponse\n";
    exit(1);
}

echo "✅ Token ottenuto\n\n";

// Step 2: Prima richiesta - dovrebbe essere creata
echo "📋 STEP 2: Prima richiesta (dovrebbe essere CREATA)\n";
$requestData = [
    'type_id' => 1,
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio
    'reason' => 'Test controllo duplicati - prima richiesta',
    'date_from' => '2025-02-01',
    'date_to' => '2025-02-05'
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

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data1 = json_decode($response1, true);

echo "Status HTTP: $httpCode1\n";
echo $data1['success'] ? "✅ SUCCESS" : "❌ FAILED";
echo "\n";

if ($data1['success']) {
    echo "📊 Prima richiesta:\n";
    echo "   ID: " . $data1['data']['id'] . "\n";
    echo "   Status: " . $data1['data']['status'] . "\n";
    echo "   Created by: " . $data1['data']['created_by']['first_name'] . " " . $data1['data']['created_by']['last_name'] . "\n";
    echo "   Created at: " . $data1['data']['created_at'] . "\n";
    echo "   Is duplicate: " . (isset($data1['data']['is_duplicate']) ? ($data1['data']['is_duplicate'] ? 'YES' : 'NO') : 'NO') . "\n";
    echo "   Message: " . $data1['message'] . "\n";
    
    $firstRequestId = $data1['data']['id'];
} else {
    echo "❌ ERRORE nella prima richiesta: " . ($data1['error'] ?? 'Errore sconosciuto') . "\n";
    exit(1);
}

echo "\n";

// Step 3: Seconda richiesta identica - dovrebbe restituire quella esistente
echo "📋 STEP 3: Seconda richiesta IDENTICA (dovrebbe restituire quella ESISTENTE)\n";
$requestData2 = [
    'type_id' => 1, // Stesso tipo
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio (stesso paziente)
    'reason' => 'Test controllo duplicati - seconda richiesta (dovrebbe essere ignorata)',
    'date_from' => '2025-02-10', // Date diverse
    'date_to' => '2025-02-15'
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
echo $data2['success'] ? "✅ SUCCESS" : "❌ FAILED";
echo "\n";

if ($data2['success']) {
    echo "📊 Seconda richiesta:\n";
    echo "   ID: " . $data2['data']['id'] . "\n";
    echo "   Status: " . $data2['data']['status'] . "\n";
    echo "   Created by: " . $data2['data']['created_by']['first_name'] . " " . $data2['data']['created_by']['last_name'] . "\n";
    echo "   Created at: " . $data2['data']['created_at'] . "\n";
    echo "   Is duplicate: " . (isset($data2['data']['is_duplicate']) ? ($data2['data']['is_duplicate'] ? 'YES' : 'NO') : 'NO') . "\n";
    echo "   Message: " . $data2['message'] . "\n";
    
    if (isset($data2['data']['duplicate_message'])) {
        echo "   Duplicate message: " . $data2['data']['duplicate_message'] . "\n";
    }
    
    $secondRequestId = $data2['data']['id'];
} else {
    echo "❌ ERRORE nella seconda richiesta: " . ($data2['error'] ?? 'Errore sconosciuto') . "\n";
    exit(1);
}

echo "\n";

// Step 4: Verifica risultati
echo "🔍 STEP 4: Verifica controllo duplicati\n";
echo "=====================================\n";

if ($firstRequestId === $secondRequestId) {
    echo "✅ CONTROLLO DUPLICATI FUNZIONA: Stesso ID restituito ($firstRequestId)\n";
    
    // Verifica status code
    if ($httpCode1 === 201 && $httpCode2 === 200) {
        echo "✅ STATUS CODE CORRETTI: Prima=201 (Created), Seconda=200 (OK)\n";
    } else {
        echo "⚠️  STATUS CODE: Prima=$httpCode1, Seconda=$httpCode2 (attesi: 201, 200)\n";
    }
    
    // Verifica flag is_duplicate
    $isDuplicate1 = isset($data1['data']['is_duplicate']) ? $data1['data']['is_duplicate'] : false;
    $isDuplicate2 = isset($data2['data']['is_duplicate']) ? $data2['data']['is_duplicate'] : false;
    
    if (!$isDuplicate1 && $isDuplicate2) {
        echo "✅ FLAG IS_DUPLICATE CORRETTI: Prima=false, Seconda=true\n";
    } else {
        echo "⚠️  FLAG IS_DUPLICATE: Prima=" . ($isDuplicate1 ? 'true' : 'false') . ", Seconda=" . ($isDuplicate2 ? 'true' : 'false') . " (attesi: false, true)\n";
    }
    
    // Verifica messaggi diversi
    if ($data1['message'] !== $data2['message']) {
        echo "✅ MESSAGGI DIVERSI: Corretti per nuova vs duplicata\n";
    } else {
        echo "⚠️  MESSAGGI IDENTICI: Dovrebbero essere diversi\n";
    }
    
} else {
    echo "❌ CONTROLLO DUPLICATI NON FUNZIONA: ID diversi ($firstRequestId vs $secondRequestId)\n";
}

echo "\n";

// Step 5: Test con tipo diverso - dovrebbe creare nuova richiesta
echo "📋 STEP 5: Richiesta con TIPO DIVERSO (dovrebbe creare NUOVA richiesta)\n";
$requestData3 = [
    'type_id' => 2, // Tipo diverso
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio
    'reason' => 'Test tipo diverso - dovrebbe creare nuova richiesta'
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
echo $data3['success'] ? "✅ SUCCESS" : "❌ FAILED";
echo "\n";

if ($data3['success']) {
    echo "📊 Richiesta tipo diverso:\n";
    echo "   ID: " . $data3['data']['id'] . "\n";
    echo "   Type ID: " . $data3['data']['type_id'] . "\n";
    echo "   Status: " . $data3['data']['status'] . "\n";
    echo "   Is duplicate: " . (isset($data3['data']['is_duplicate']) ? ($data3['data']['is_duplicate'] ? 'YES' : 'NO') : 'NO') . "\n";
    
    $thirdRequestId = $data3['data']['id'];
    
    if ($thirdRequestId !== $firstRequestId) {
        echo "✅ TIPO DIVERSO: Nuova richiesta creata (ID $thirdRequestId diverso da $firstRequestId)\n";
    } else {
        echo "❌ TIPO DIVERSO: Stesso ID restituito (non dovrebbe succedere)\n";
    }
} else {
    echo "❌ ERRORE nella richiesta tipo diverso: " . ($data3['error'] ?? 'Errore sconosciuto') . "\n";
}

curl_close($ch);

echo "\n🎯 TEST COMPLETATO!\n";
?> 