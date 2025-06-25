<?php
require_once '../config/bootstrap.php';

echo "🕐 TEST TIMEZONE UTC NEGLI ENDPOINT\n";
echo "=====================================\n\n";

// Verifica configurazione timezone
echo "⚙️  STEP 1: Verifica configurazione timezone\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Current time UTC: " . date('Y-m-d H:i:s T') . "\n";

// Step 2: Login per token
echo "\n🔐 STEP 2: Login per ottenere token JWT\n";
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
    echo "Response: " . $loginResponse . "\n";
    exit(1);
}

echo "✅ Token ottenuto\n";

// Step 3: Test creazione richiesta con verifica timestamp UTC
echo "\n📅 STEP 3: Test creazione richiesta con verifica timestamp UTC\n";

// Registra timestamp prima della richiesta
$beforeRequest = new DateTime('now', new DateTimeZone('UTC'));

$requestData = [
    'type_id' => 1,
    'reason' => 'Test verifica timestamp UTC',
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

// Registra timestamp dopo la richiesta
$afterRequest = new DateTime('now', new DateTimeZone('UTC'));

echo "Status: $httpCode\n";

if ($data['success']) {
    echo "✅ SUCCESS - Richiesta creata\n\n";
    
    echo "🔍 ANALISI DETTAGLIATA TIMESTAMP:\n";
    echo "   Before request: " . $beforeRequest->format('Y-m-d\TH:i:s\Z') . "\n";
    echo "   Created at:     " . $data['data']['created_at'] . "\n";
    echo "   After request:  " . $afterRequest->format('Y-m-d\TH:i:s\Z') . "\n";
    echo "   Estimated comp: " . $data['data']['estimated_completion'] . "\n\n";
    
    // Verifica formato ISO8601 con Z
    $createdAt = $data['data']['created_at'];
    $estimatedCompletion = $data['data']['estimated_completion'];
    
    echo "📋 VERIFICHE FORMATO:\n";
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $createdAt)) {
        echo "   ✅ created_at ha formato UTC corretto (ISO8601 con Z)\n";
    } else {
        echo "   ❌ created_at NON ha formato UTC corretto: $createdAt\n";
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $estimatedCompletion)) {
        echo "   ✅ estimated_completion ha formato UTC corretto (ISO8601 con Z)\n";
    } else {
        echo "   ❌ estimated_completion NON ha formato UTC corretto: $estimatedCompletion\n";
    }
    
    echo "\n⏰ VERIFICHE TEMPORALI:\n";
    
    // Verifica che created_at sia tra before e after
    try {
        $createdDateTime = new DateTime($createdAt);
        
        if ($createdDateTime >= $beforeRequest && $createdDateTime <= $afterRequest) {
            echo "   ✅ created_at è nel range temporale corretto\n";
        } else {
            echo "   ❌ created_at NON è nel range temporale corretto\n";
        }
        
        // Verifica differenza con ora corrente (deve essere minima)
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $diff = abs($now->getTimestamp() - $createdDateTime->getTimestamp());
        
        if ($diff < 10) { // Meno di 10 secondi di differenza
            echo "   ✅ created_at è sincronizzato con UTC (differenza: {$diff}s)\n";
        } else {
            echo "   ⚠️  created_at potrebbe non essere UTC (differenza: {$diff}s)\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Errore parsing created_at: " . $e->getMessage() . "\n";
    }
    
    // Verifica estimated_completion
    try {
        $estimatedDateTime = new DateTime($estimatedCompletion);
        
        if ($estimatedDateTime > $createdDateTime) {
            echo "   ✅ estimated_completion è dopo created_at\n";
        } else {
            echo "   ❌ estimated_completion NON è dopo created_at\n";
        }
        
        // Calcola giorni lavorativi tra created_at e estimated_completion
        $workDays = 0;
        $tempDate = clone $createdDateTime;
        
        while ($tempDate < $estimatedDateTime) {
            $tempDate->add(new DateInterval('P1D'));
            if ($tempDate->format('N') <= 5) { // Lunedì-Venerdì
                $workDays++;
            }
        }
        
        echo "   📊 Giorni lavorativi calcolati: $workDays\n";
        echo "   📋 Tipo richiesta: " . $data['data']['request_type'] . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ Errore parsing estimated_completion: " . $e->getMessage() . "\n";
    }
    
    echo "\n🌍 TEST CONVERSIONE TIMEZONE CLIENT:\n";
    
    // Simula conversione timezone client
    $utcTimestamp = $data['data']['created_at'];
    
    // JavaScript-like conversion
    $jsDate = new DateTime($utcTimestamp);
    $jsDate->setTimezone(new DateTimeZone('Europe/Rome'));
    echo "   🇮🇹 Ora italiana: " . $jsDate->format('d/m/Y H:i:s T') . "\n";
    
    $jsDate->setTimezone(new DateTimeZone('America/New_York'));
    echo "   🇺🇸 Ora New York: " . $jsDate->format('d/m/Y H:i:s T') . "\n";
    
    $jsDate->setTimezone(new DateTimeZone('Asia/Tokyo'));
    echo "   🇯🇵 Ora Tokyo: " . $jsDate->format('d/m/Y H:i:s T') . "\n";
    
} else {
    echo "❌ FAILED\n";
    echo "Errore: " . ($data['message'] ?? 'Unknown error') . "\n";
    if (isset($data['errors'])) {
        print_r($data['errors']);
    }
}

curl_close($ch);

echo "\n🏁 Test timezone completato!\n";
echo "=====================================\n";
echo "LEGENDA:\n";
echo "✅ = Gestione timezone corretta\n";
echo "❌ = Problema gestione timezone\n";
echo "⚠️  = Warning, possibile problema\n";
echo "📊 = Informazione\n";
?> 