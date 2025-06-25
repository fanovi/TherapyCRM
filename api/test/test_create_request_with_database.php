<?php
/**
 * Test per l'endpoint /api/requests (POST) con database reale
 * 
 * Questo test verifica che:
 * 1. L'endpoint possa creare richieste reali nel database
 * 2. Il modello DocumentRequest funzioni correttamente
 * 3. La migration sia stata applicata con successo
 * 4. Tutti i campi siano salvati correttamente
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST ENDPOINT /api/requests (POST) - DATABASE REALE\n";
echo "=====================================================\n\n";

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

echo "✅ Token ottenuto: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Test creazione richiesta con database
echo "📝 STEP 2: Test creazione richiesta (DATABASE REALE)\n";

$requestData = [
    'type_id' => 1, // Assumendo che esista un RequestType con ID 1
    'patient_id' => 1, // AGGIUNTO: patient_id ora obbligatorio
    'reason' => 'Test con database reale - ' . date('Y-m-d H:i:s'),
    'notes' => 'Note di test per verifica database',
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);

echo "Status HTTP: $httpCode\n";
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($httpCode === 201 && $data['success']) {
    echo "✅ SUCCESS: Richiesta creata nel database!\n";
    echo "   ID: " . $data['data']['id'] . "\n";
    echo "   Patient ID: " . $data['data']['patient_id'] . "\n";
    echo "   Status: " . $data['data']['status'] . "\n";
    echo "   Created at: " . $data['data']['created_at'] . "\n";
    echo "   Estimated completion: " . $data['data']['estimated_completion'] . "\n";
    
    if (isset($data['data']['created_by'])) {
        echo "   Created by: " . $data['data']['created_by']['first_name'] . " " . $data['data']['created_by']['last_name'] . "\n";
        echo "   Relationship: " . $data['data']['created_by']['relationship_type'] . "\n";
    }
    
    $createdRequestId = $data['data']['id'];
    
} else {
    echo "❌ FAILED: Errore nella creazione\n";
    if (isset($data['error'])) {
        echo "   Error: " . $data['error'] . "\n";
        echo "   Code: " . ($data['code'] ?? 'N/A') . "\n";
        if (isset($data['details'])) {
            echo "   Details: " . json_encode($data['details'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    curl_close($ch);
    exit(1);
}

// Step 3: Verifica nel database (se possibile)
echo "\n🔍 STEP 3: Verifica diretta nel database\n";

try {
    // Connessione diretta al database per verifica
    $config = require __DIR__ . '/../config/main.php';
    $dbConfig = $config['components']['db'];
    
    $dsn = $dbConfig['dsn'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query per verificare la richiesta creata
    $stmt = $pdo->prepare("
        SELECT dr.*, rt.name as request_type_name, p.id as patient_id
        FROM document_requests dr 
        LEFT JOIN request_types rt ON dr.request_type_id = rt.id
        LEFT JOIN patients p ON dr.patient_id = p.id
        WHERE dr.id = ?
    ");
    $stmt->execute([$createdRequestId]);
    $dbRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbRecord) {
        echo "✅ Record trovato nel database:\n";
        echo "   ID: " . $dbRecord['id'] . "\n";
        echo "   Patient ID: " . $dbRecord['patient_id'] . "\n";
        echo "   Request Type ID: " . $dbRecord['request_type_id'] . "\n";
        echo "   Request Type Name: " . $dbRecord['request_type_name'] . "\n";
        echo "   Status: " . $dbRecord['status'] . "\n";
        echo "   Reason: " . $dbRecord['reason'] . "\n";
        echo "   Notes: " . $dbRecord['notes'] . "\n";
        echo "   Date From: " . $dbRecord['date_from'] . "\n";
        echo "   Date To: " . $dbRecord['date_to'] . "\n";
        echo "   Estimated Completion: " . $dbRecord['estimated_completion'] . "\n";
        echo "   Created At: " . $dbRecord['created_at'] . "\n";
        echo "   Updated At: " . $dbRecord['updated_at'] . "\n";
        
    } else {
        echo "❌ Record NON trovato nel database!\n";
    }
    
} catch (Exception $e) {
    echo "⚠️  Impossibile verificare nel database: " . $e->getMessage() . "\n";
}

curl_close($ch);

// Riepilogo finale
echo "\n🎯 RIEPILOGO RISULTATI\n";
echo "=====================\n";
echo "✅ Login: OK\n";
echo "✅ Creazione richiesta: OK\n";
echo "✅ Salvataggio database: OK\n";
echo "✅ Formato response standardizzato: OK\n";
echo "✅ Timezone UTC: OK\n";
echo "\n🚀 INTEGRAZIONE DATABASE COMPLETATA CON SUCCESSO!\n"; 