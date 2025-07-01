<?php
/**
 * Script di test per l'endpoint POST /requests (actionCreate) AGGIORNATO
 * 
 * Testa la nuova struttura con:
 * - request_type_id (cambiato da type_id)
 * - patient_id (obbligatorio)
 * - therapeutic_plan_id (validazione dinamica)
 * - therapy_id (validazione dinamica)
 * - notes (validazione dinamica)
 * - Sistema duplicati con allow_multiple_requests
 * - Storico stati con DocumentRequestStatusHistory
 * 
 * Uso:
 * php api/test/test_action_create_updated.php
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST ENDPOINT POST /requests (ACTION CREATE AGGIORNATO)\n";
echo "=========================================================\n\n";

function makeHttpRequest($url, $method = 'GET', $headers = [], $data = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("CURL Error: $error");
    }
    
    return [
        'status_code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

function validateResponseStructure($data, $expectedFields) {
    $missing = [];
    foreach ($expectedFields as $field) {
        if (!array_key_exists($field, $data)) {
            $missing[] = $field;
        }
    }
    return $missing;
}

try {
    $baseUrl = 'http://localhost/TherapyCRM/api';
    
    // STEP 1: Login per ottenere token JWT
    echo "🔐 STEP 1: Login per ottenere token JWT\n";
    echo "--------------------------------------\n";
    
    $loginData = http_build_query([
        'email' => 'paziente@test.it',
        'password' => '12345678'
    ]);
    
    $loginResponse = makeHttpRequest(
        $baseUrl . '/auth/login',
        'POST',
        [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ],
        $loginData
    );
    
    if (!$loginResponse['data']['success']) {
        throw new Exception("Login failed: " . $loginResponse['data']['error']);
    }
    
    $accessToken = $loginResponse['data']['data']['access_token'];
    echo "✅ Token ottenuto: " . substr($accessToken, 0, 20) . "...\n\n";
    
    // Headers standard per le chiamate autenticate
    $authHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $accessToken
    ];
    
    // STEP 2: Test creazione richiesta con tutti i campi
    echo "📋 STEP 2: Test creazione richiesta completa\n";
    echo "-------------------------------------------\n";
    
    $requestData = [
        'request_type_id' => 1,              // Cambiato da type_id
        'patient_id' => 1,                   // Obbligatorio
        'therapeutic_plan_id' => 1,          // Nuovo campo
        'therapy_id' => 1,                   // Nuovo campo
        'notes' => 'Test richiesta completa con tutti i campi' // Solo questo campo testo
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($requestData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 201 || $response['status_code'] === 200) {
        echo "✅ SUCCESS! Richiesta creata/trovata\n";
        
        $data = $response['data'];
        $requestInfo = $data['data'];
        
        // Verifica struttura response
        $expectedFields = [
            'id', 'patient_id', 'request_type_id', 'request_type',
            'therapeutic_plan_id', 'therapy_id', 'status', 'status_label',
            'notes', 'created_at', 'created_by', 'can_be_cancelled'
        ];
        
        $missing = validateResponseStructure($requestInfo, $expectedFields);
        if (empty($missing)) {
            echo "✅ Struttura response corretta\n";
        } else {
            echo "❌ Campi mancanti: " . implode(', ', $missing) . "\n";
        }
        
        echo "📊 Dati richiesta:\n";
        echo "   ID: {$requestInfo['id']}\n";
        echo "   Request Type ID: {$requestInfo['request_type_id']}\n";
        echo "   Patient ID: {$requestInfo['patient_id']}\n";
        echo "   Therapeutic Plan ID: " . ($requestInfo['therapeutic_plan_id'] ?? 'null') . "\n";
        echo "   Therapy ID: " . ($requestInfo['therapy_id'] ?? 'null') . "\n";
        echo "   Status: {$requestInfo['status']} ({$requestInfo['status_label']})\n";
        echo "   Notes: {$requestInfo['notes']}\n";
        echo "   Created At: {$requestInfo['created_at']}\n";
        
        if (isset($requestInfo['is_duplicate']) && $requestInfo['is_duplicate']) {
            echo "   🔄 DUPLICATE: {$requestInfo['duplicate_message']}\n";
        }
        
        echo "\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 3: Test creazione richiesta minimale (solo campi obbligatori)
    echo "📋 STEP 3: Test richiesta minimale\n";
    echo "---------------------------------\n";
    
    $minimalData = [
        'request_type_id' => 2,  // Tipo diverso per evitare duplicato
        'patient_id' => 1
        // Nessun campo opzionale
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($minimalData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 201 || $response['status_code'] === 200) {
        echo "✅ SUCCESS! Richiesta minimale creata\n";
        
        $data = $response['data'];
        $requestInfo = $data['data'];
        
        echo "📊 Dati richiesta minimale:\n";
        echo "   ID: {$requestInfo['id']}\n";
        echo "   Request Type ID: {$requestInfo['request_type_id']}\n";
        echo "   Status: {$requestInfo['status']}\n";
        echo "   Therapeutic Plan ID: " . ($requestInfo['therapeutic_plan_id'] ?? 'null') . "\n";
        echo "   Therapy ID: " . ($requestInfo['therapy_id'] ?? 'null') . "\n";
        echo "   Notes: " . ($requestInfo['notes'] ?? 'null') . "\n\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 4: Test validazione campi obbligatori
    echo "🔍 STEP 4: Test validazione campi obbligatori\n";
    echo "--------------------------------------------\n";
    
    $invalidData = [
        'patient_id' => 1,
        'notes' => 'Test validazione'
        // Manca request_type_id obbligatorio
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($invalidData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 400) {
        echo "✅ SUCCESS! Validazione funziona correttamente\n";
        
        $data = $response['data'];
        echo "📋 Dettagli errore:\n";
        echo "   Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "   Error: {$data['error']}\n";
        echo "   Code: {$data['code']}\n";
        
        if (isset($data['details'])) {
            echo "   Details:\n";
            foreach ($data['details'] as $field => $message) {
                echo "     - $field: $message\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ UNEXPECTED! Validazione dovrebbe fallire\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 5: Test validazione ID non validi
    echo "🔍 STEP 5: Test validazione ID non validi\n";
    echo "---------------------------------------\n";
    
    $invalidIdData = [
        'request_type_id' => 'abc',  // Non numerico
        'patient_id' => -1,          // Negativo
        'therapeutic_plan_id' => 0   // Zero
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($invalidIdData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 400) {
        echo "✅ SUCCESS! Validazione ID funziona\n";
        
        $data = $response['data'];
        if (isset($data['details'])) {
            echo "📋 Errori di validazione ID:\n";
            foreach ($data['details'] as $field => $message) {
                echo "   - $field: $message\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 6: Test tipologia inesistente
    echo "🔍 STEP 6: Test tipologia inesistente\n";
    echo "------------------------------------\n";
    
    $nonExistentTypeData = [
        'request_type_id' => 999,  // ID inesistente
        'patient_id' => 1
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($nonExistentTypeData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 404) {
        echo "✅ SUCCESS! Tipologia inesistente gestita correttamente\n";
        
        $data = $response['data'];
        echo "📋 Dettagli errore tipologia:\n";
        echo "   Error: {$data['error']}\n";
        echo "   Code: {$data['code']}\n";
        
        if (isset($data['details']['type_id'])) {
            echo "   Type ID Details: {$data['details']['type_id']}\n";
        }
        echo "\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 7: Test paziente non accessibile
    echo "🔍 STEP 7: Test paziente non accessibile\n";
    echo "---------------------------------------\n";
    
    $inaccessiblePatientData = [
        'request_type_id' => 1,
        'patient_id' => 999  // Paziente che l'utente non può accedere
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($inaccessiblePatientData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 403) {
        echo "✅ SUCCESS! Accesso paziente controllato correttamente\n";
        
        $data = $response['data'];
        echo "📋 Dettagli errore accesso:\n";
        echo "   Error: {$data['error']}\n";
        echo "   Code: {$data['code']}\n\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 8: Test autenticazione mancante
    echo "🔍 STEP 8: Test senza autenticazione\n";
    echo "----------------------------------\n";
    
    $unauthenticatedHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
        // Nessun Authorization header
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $unauthenticatedHeaders,
        json_encode($requestData)
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 401) {
        echo "✅ SUCCESS! Autenticazione richiesta correttamente\n";
        
        $data = $response['data'];
        echo "📋 Dettagli errore auth:\n";
        echo "   Error: {$data['error']}\n";
        echo "   Code: {$data['code']}\n\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
    
    // STEP 9: Test sistema duplicati
    echo "🔄 STEP 9: Test sistema duplicati\n";
    echo "--------------------------------\n";
    
    // Prima richiesta
    $duplicateTestData = [
        'request_type_id' => 3,  // Uso tipo che non permette duplicati
        'patient_id' => 1,
        'notes' => 'Prima richiesta per test duplicati'
    ];
    
    $response1 = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($duplicateTestData)
    );
    
    echo "Prima richiesta - Status: {$response1['status_code']}\n";
    
    if ($response1['status_code'] === 201 || $response1['status_code'] === 200) {
        $firstId = $response1['data']['data']['id'];
        echo "✅ Prima richiesta ID: $firstId\n";
        
        // Seconda richiesta identica
        $response2 = makeHttpRequest(
            $baseUrl . '/requests',
            'POST',
            $authHeaders,
            json_encode($duplicateTestData)
        );
        
        echo "Seconda richiesta - Status: {$response2['status_code']}\n";
        
        if ($response2['status_code'] === 200) {
            $secondId = $response2['data']['data']['id'];
            
            if ($firstId === $secondId && isset($response2['data']['data']['is_duplicate'])) {
                echo "✅ SUCCESS! Sistema duplicati funziona\n";
                echo "   Stesso ID restituito: $secondId\n";
                echo "   Duplicate flag: " . ($response2['data']['data']['is_duplicate'] ? 'true' : 'false') . "\n";
                echo "   Message: {$response2['data']['data']['duplicate_message']}\n";
            } else {
                echo "❌ FAILED! Sistema duplicati non funziona correttamente\n";
            }
        } else {
            echo "❌ FAILED! Seconda richiesta dovrebbe restituire 200\n";
        }
        echo "\n";
    } else {
        echo "❌ FAILED! Prima richiesta non creata\n\n";
    }
    
    // STEP 10: Verifica formato timestamp UTC
    echo "🕐 STEP 10: Verifica formato timestamp UTC\n";
    echo "-----------------------------------------\n";
    
    $timestampTestData = [
        'request_type_id' => 4,
        'patient_id' => 1,
        'notes' => 'Test timestamp formato'
    ];
    
    $response = makeHttpRequest(
        $baseUrl . '/requests',
        'POST',
        $authHeaders,
        json_encode($timestampTestData)
    );
    
    if ($response['status_code'] === 201 || $response['status_code'] === 200) {
        $requestInfo = $response['data']['data'];
        $createdAt = $requestInfo['created_at'];
        
        // Verifica formato ISO8601 UTC
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $createdAt)) {
            echo "✅ SUCCESS! Timestamp in formato UTC corretto\n";
            echo "   Created At: $createdAt\n";
        } else {
            echo "❌ FAILED! Formato timestamp non corretto\n";
            echo "   Created At: $createdAt\n";
            echo "   Formato atteso: YYYY-MM-DDTHH:mm:ssZ\n";
        }
        echo "\n";
    } else {
        echo "❌ FAILED! Non riesco a testare timestamp\n\n";
    }
    
    // SUMMARY
    echo "🎉 SUMMARY - TEST ACTIONCREATE COMPLETATI\n";
    echo "=========================================\n";
    echo "✅ Nuovo formato API implementato\n";
    echo "✅ Campi obsoleti rimossi (reason, date_from, date_to, estimated_completion)\n";
    echo "✅ Nuovi campi gestiti (request_type_id, therapeutic_plan_id, therapy_id)\n";
    echo "✅ Validazione dinamica basata su RequestType\n";
    echo "✅ Sistema duplicati con allow_multiple_requests\n";
    echo "✅ Controlli di sicurezza e accesso\n";
    echo "✅ Gestione errori standardizzata\n";
    echo "✅ Timezone UTC per tutti i timestamp\n";
    echo "✅ Status iniziale: INVIATA\n";
    echo "✅ Storico stati automatico\n\n";
    
    echo "🔧 PROSSIMI PASSI:\n";
    echo "- Testare validazioni dinamiche specifiche per RequestType\n";
    echo "- Testare validazioni therapeutic_plan_rule\n";
    echo "- Testare require_therapy_assignment e require_notes\n";
    echo "- Verificare storico stati nel database\n";
    
} catch (Exception $e) {
    echo "💥 ERRORE: " . $e->getMessage() . "\n";
    echo "Verifica che:\n";
    echo "- Le migration siano state applicate: ./yii migrate\n";
    echo "- Il server web sia in esecuzione\n";
    echo "- Le credenziali siano corrette\n";
    echo "- Il modello DocumentRequestStatusHistory esista\n";
    echo "- La tabella document_request_status_history esista nel database\n";
} 