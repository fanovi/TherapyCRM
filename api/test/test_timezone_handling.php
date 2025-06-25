<?php
require_once '../config/bootstrap.php';

echo "🕐 TEST GESTIONE TIMEZONE UTC\n";
echo "=====================================\n\n";

// Verifica configurazione timezone
echo "⚙️  STEP 1: Verifica configurazione timezone\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Current time UTC: " . date('Y-m-d H:i:s T') . "\n";
echo "Current time ISO8601: " . date('c') . "\n\n";

// Step 2: Login per token
echo "🔐 STEP 2: Login per ottenere token JWT\n";
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
$token = $loginData['access_token'] ?? null;

if (!$token) {
    echo "❌ ERRORE: Impossibile ottenere token\n";
    exit(1);
}

echo "✅ Token ottenuto\n\n";

// Step 3: Test creazione richiesta con timestamp UTC
echo "📅 STEP 3: Test creazione richiesta con timestamp UTC\n";

$requestData = [
    'type_id' => 1,
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio
    'reason' => 'Test gestione timezone UTC',
    'date_from' => '2025-02-01',
    'date_to' => '2025-02-05',
    'notes' => 'Verifica che i timestamp siano in UTC'
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

echo "Status: $httpCode\n";

if ($data['success']) {
    echo "✅ SUCCESS - Richiesta creata\n\n";
    
    echo "📊 ANALISI TIMESTAMP:\n";
    echo "   Created at: " . $data['data']['created_at'] . "\n";
    echo "   Estimated completion: " . $data['data']['estimated_completion'] . "\n";
    
    // Verifica formato ISO8601 con Z
    $createdAt = $data['data']['created_at'];
    $estimatedCompletion = $data['data']['estimated_completion'];
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $createdAt)) {
        echo "   ✅ created_at ha formato UTC corretto (ISO8601 con Z)\n";
    } else {
        echo "   ❌ created_at NON ha formato UTC corretto\n";
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $estimatedCompletion)) {
        echo "   ✅ estimated_completion ha formato UTC corretto (ISO8601 con Z)\n";
    } else {
        echo "   ❌ estimated_completion NON ha formato UTC corretto\n";
    }
    
    // Verifica che sia realmente UTC (confronto con ora corrente)
    $createdDateTime = new DateTime($createdAt);
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $diff = abs($now->getTimestamp() - $createdDateTime->getTimestamp());
    
    if ($diff < 60) { // Meno di 1 minuto di differenza
        echo "   ✅ Timestamp sembra essere realmente in UTC (differenza: {$diff}s)\n";
    } else {
        echo "   ⚠️  Timestamp potrebbe non essere in UTC (differenza: {$diff}s)\n";
    }
    
} else {
    echo "❌ FAILED\n";
    echo "Errore: " . ($data['message'] ?? 'Unknown error') . "\n";
}

curl_close($ch);

echo "\n🏁 Test completato!\n";
echo "=====================================\n";
echo "LEGENDA:\n";
echo "✅ = OK, gestione timezone corretta\n";
echo "❌ = ERRORE, gestione timezone non corretta\n";
echo "⚠️  = WARNING, possibile problema\n";
?> 