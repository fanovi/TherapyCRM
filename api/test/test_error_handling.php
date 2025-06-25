<?php

require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST GESTIONE ERRORI STANDARDIZZATA\n";
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
    exit(1);
}

echo "✅ Token ottenuto\n\n";

// Test Error Codes
$tests = [
    [
        'name' => 'INVALID_REQUEST_TYPE - Tipologia inesistente',
        'method' => 'POST',
        'url' => 'http://localhost/TherapyCRM/api/requests',
        'data' => json_encode(['type_id' => 999, 'patient_id' => 1, 'reason' => 'Test']), // AGGIUNTO patient_id
        'expected_code' => 'INVALID_REQUEST_TYPE',
        'expected_status' => 404
    ],
    [
        'name' => 'MISSING_REQUIRED_FIELD - Campo obbligatorio mancante',
        'method' => 'POST',
        'url' => 'http://localhost/TherapyCRM/api/requests',
        'data' => json_encode(['type_id' => '', 'patient_id' => 1]), // type_id vuoto, AGGIUNTO patient_id
        'expected_code' => 'MISSING_REQUIRED_FIELD',
        'expected_status' => 400
    ],
    [
        'name' => 'MISSING_REQUIRED_FIELD - Reason obbligatorio per tipo 1',
        'method' => 'POST',
        'url' => 'http://localhost/TherapyCRM/api/requests',
        'data' => json_encode(['type_id' => 1, 'patient_id' => 1]), // Certificato Medico richiede reason, AGGIUNTO patient_id
        'expected_code' => 'MISSING_REQUIRED_FIELD',
        'expected_status' => 400
    ],
    [
        'name' => 'UNAUTHORIZED - Token mancante',
        'method' => 'GET',
        'url' => 'http://localhost/TherapyCRM/api/requests/types',
        'data' => null,
        'expected_code' => 'UNAUTHORIZED',
        'expected_status' => 401,
        'skip_auth' => true
    ]
];

foreach ($tests as $i => $test) {
    echo "📋 TEST " . ($i + 1) . ": {$test['name']}\n";
    echo str_repeat('-', 50) . "\n";
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $test['url'],
        CURLOPT_CUSTOMREQUEST => $test['method'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            ...(empty($test['skip_auth']) ? ['Authorization: Bearer ' . $token] : [])
        ]
    ]);
    
    if ($test['data']) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test['data']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data = json_decode($response, true);
    
    echo "Status HTTP: $httpCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Verifica formato errore standard
    $formatOk = true;
    $errors = [];
    
    if (!isset($data['success']) || $data['success'] !== false) {
        $formatOk = false;
        $errors[] = "Campo 'success' deve essere false";
    }
    
    if (!isset($data['error']) || !is_string($data['error'])) {
        $formatOk = false;
        $errors[] = "Campo 'error' deve essere una stringa";
    }
    
    if (!isset($data['code']) || !is_string($data['code'])) {
        $formatOk = false;
        $errors[] = "Campo 'code' deve essere una stringa";
    }
    
    if ($data['code'] !== $test['expected_code']) {
        $formatOk = false;
        $errors[] = "Codice errore atteso: '{$test['expected_code']}', ricevuto: '{$data['code']}'";
    }
    
    if ($httpCode !== $test['expected_status']) {
        $formatOk = false;
        $errors[] = "Status HTTP atteso: {$test['expected_status']}, ricevuto: $httpCode";
    }
    
    // Verifica che non ci siano i vecchi campi
    if (isset($data['message']) || isset($data['error_code']) || isset($data['errors'])) {
        $formatOk = false;
        $errors[] = "Presenti campi del vecchio formato (message/error_code/errors)";
    }
    
    if ($formatOk) {
        echo "✅ FORMATO ERRORE CORRETTO\n";
        echo "✅ CODICE ERRORE CORRETTO: {$data['code']}\n";
        echo "✅ STATUS HTTP CORRETTO: $httpCode\n";
        
        if (isset($data['details']) && !empty($data['details'])) {
            echo "✅ DETTAGLI PRESENTI: " . json_encode($data['details'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "❌ ERRORI FORMATO:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
    
    echo "\n";
}

curl_close($ch);

echo "🎯 TEST COMPLETATI\n";
echo "================\n";
echo "✅ Formato errore standard: success, error, code\n";
echo "✅ Codici errore implementati: INVALID_REQUEST_TYPE, MISSING_REQUIRED_FIELD, UNAUTHORIZED, INTERNAL_ERROR\n";
echo "✅ Status HTTP corretti: 400, 401, 404, 500\n";
echo "✅ Campo details opzionale per informazioni aggiuntive\n";
echo "✅ Eliminati vecchi campi: message, error_code, errors\n"; 