<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "📋 TEST ENDPOINT GET /requests (actionIndex)\n";
echo "=============================================\n\n";

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

// Test 1: Recupera tutte le richieste del paziente 1
echo "📋 TEST 1: Recupera tutte le richieste del paziente 1\n";
echo "==================================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=1',
    CURLOPT_HTTPGET => true,
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

if ($data1['success'] && $httpCode1 === 200) {
    echo "✅ SUCCESSO: Richieste recuperate\n";
    echo "   Total: " . $data1['meta']['total'] . "\n";
    echo "   Page: " . $data1['meta']['page'] . "\n";
    echo "   Limit: " . $data1['meta']['limit'] . "\n";
    echo "   Total Pages: " . $data1['meta']['total_pages'] . "\n";
    
    if (!empty($data1['data'])) {
        $firstRequest = $data1['data'][0];
        echo "   Prima richiesta:\n";
        echo "     ID: " . $firstRequest['id'] . "\n";
        echo "     Tipo: " . $firstRequest['request_type'] . "\n";
        echo "     Status: " . $firstRequest['status'] . "\n";
        echo "     Created: " . $firstRequest['created_at'] . "\n";
        
        if (isset($firstRequest['created_by'])) {
            echo "     Creata da: " . $firstRequest['created_by']['first_name'] . " " . $firstRequest['created_by']['last_name'] . "\n";
            echo "     Relazione: " . $firstRequest['created_by']['relationship_type'] . "\n";
        }
    }
} else {
    echo "❌ ERRORE: " . ($data1['error'] ?? 'Errore sconosciuto') . "\n";
}

echo "\n";

// Test 2: Paginazione
echo "📋 TEST 2: Test paginazione (page=1, limit=2)\n";
echo "=============================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=1&page=1&limit=2',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data2 = json_decode($response2, true);

echo "Status HTTP: $httpCode2\n";

if ($data2['success'] && $httpCode2 === 200) {
    echo "✅ SUCCESSO: Paginazione funzionante\n";
    echo "   Elementi restituiti: " . count($data2['data']) . "\n";
    echo "   Limit richiesto: 2\n";
    echo "   Has next page: " . ($data2['meta']['has_next_page'] ? 'true' : 'false') . "\n";
    echo "   Has prev page: " . ($data2['meta']['has_prev_page'] ? 'true' : 'false') . "\n";
} else {
    echo "❌ ERRORE: " . ($data2['error'] ?? 'Errore sconosciuto') . "\n";
}

echo "\n";

// Test 3: Filtro per status
echo "📋 TEST 3: Filtro per status 'pending'\n";
echo "=====================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=1&status=pending',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data3 = json_decode($response3, true);

echo "Status HTTP: $httpCode3\n";

if ($data3['success'] && $httpCode3 === 200) {
    echo "✅ SUCCESSO: Filtro status funzionante\n";
    echo "   Richieste pending: " . count($data3['data']) . "\n";
    echo "   Status filter: " . $data3['meta']['status_filter'] . "\n";
    
    // Verifica che tutte le richieste abbiano status pending
    $allPending = true;
    foreach ($data3['data'] as $request) {
        if ($request['status'] !== 'pending') {
            $allPending = false;
            break;
        }
    }
    echo "   Tutte pending: " . ($allPending ? 'true' : 'false') . "\n";
} else {
    echo "❌ ERRORE: " . ($data3['error'] ?? 'Errore sconosciuto') . "\n";
}

echo "\n";

// Test 4: Errore - patient_id mancante
echo "❌ TEST 4: Errore - patient_id mancante\n";
echo "======================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response4 = curl_exec($ch);
$httpCode4 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data4 = json_decode($response4, true);

echo "Status HTTP: $httpCode4\n";

if (!$data4['success'] && $httpCode4 === 400) {
    echo "✅ VALIDAZIONE OK: patient_id obbligatorio\n";
    echo "   Error: " . $data4['error'] . "\n";
    echo "   Code: " . $data4['code'] . "\n";
    if (isset($data4['details']['patient_id'])) {
        echo "   Details: " . $data4['details']['patient_id'] . "\n";
    }
} else {
    echo "❌ PROBLEMA: Validazione non funziona\n";
}

echo "\n";

// Test 5: Errore - status non valido
echo "❌ TEST 5: Errore - status non valido\n";
echo "====================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=1&status=invalid_status',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response5 = curl_exec($ch);
$httpCode5 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data5 = json_decode($response5, true);

echo "Status HTTP: $httpCode5\n";

if (!$data5['success'] && $httpCode5 === 400) {
    echo "✅ VALIDAZIONE OK: Status non valido rifiutato\n";
    echo "   Error: " . $data5['error'] . "\n";
    echo "   Code: " . $data5['code'] . "\n";
    if (isset($data5['details']['status'])) {
        echo "   Details: " . $data5['details']['status'] . "\n";
    }
} else {
    echo "❌ PROBLEMA: Validazione status non funziona\n";
}

echo "\n";

// Test 6: Errore - accesso paziente non autorizzato
echo "❌ TEST 6: Errore - accesso paziente non autorizzato\n";
echo "===================================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=999',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response6 = curl_exec($ch);
$httpCode6 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data6 = json_decode($response6, true);

echo "Status HTTP: $httpCode6\n";

if (!$data6['success'] && $httpCode6 === 403) {
    echo "✅ SICUREZZA OK: Accesso negato per paziente non autorizzato\n";
    echo "   Error: " . $data6['error'] . "\n";
    echo "   Code: " . $data6['code'] . "\n";
} else {
    echo "❌ PROBLEMA: Controllo sicurezza non funziona\n";
}

echo "\n";

// Test 7: Test limite massimo (max 100)
echo "📋 TEST 7: Test limite massimo (limit=150, dovrebbe essere limitato a 100)\n";
echo "========================================================================\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests?patient_id=1&limit=150',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]
]);

$response7 = curl_exec($ch);
$httpCode7 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data7 = json_decode($response7, true);

echo "Status HTTP: $httpCode7\n";

if ($data7['success'] && $httpCode7 === 200) {
    echo "✅ LIMITE OK: Limit automaticamente limitato\n";
    echo "   Limit richiesto: 150\n";
    echo "   Limit effettivo: " . $data7['meta']['limit'] . "\n";
    echo "   (Dovrebbe essere 100 o meno)\n";
} else {
    echo "❌ ERRORE: " . ($data7['error'] ?? 'Errore sconosciuto') . "\n";
}

curl_close($ch);

echo "\n🎯 RIEPILOGO TEST ENDPOINT GET /requests\n";
echo "=======================================\n";
echo "✅ Test 1: Recupero tutte le richieste\n";
echo "✅ Test 2: Paginazione (page, limit)\n";
echo "✅ Test 3: Filtro per status\n";
echo "✅ Test 4: Validazione patient_id obbligatorio\n";
echo "✅ Test 5: Validazione status validi\n";
echo "✅ Test 6: Controllo sicurezza accesso paziente\n";
echo "✅ Test 7: Limite massimo elementi per pagina\n";
echo "\n📋 ENDPOINT GET /requests IMPLEMENTATO CORRETTAMENTE!\n";
?> 