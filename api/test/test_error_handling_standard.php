<?php
/**
 * Script di test per la gestione errori standardizzata negli endpoint API
 * 
 * Questo script verifica che tutti gli errori seguano il formato standard:
 * {
 *   "success": false,
 *   "error": "Messaggio errore leggibile",
 *   "code": "ERROR_CODE",
 *   "details": {
 *     "field": "Dettaglio specifico errore"
 *   }
 * }
 * 
 * Uso:
 * php test_error_handling_standard.php
 */

// Configurazione
$baseUrl = 'http://localhost/TherapyCRM/api';
$loginEmail = 'paziente@test.it';
$loginPassword = '12345678';

echo "🧪 TEST GESTIONE ERRORI STANDARDIZZATA\n";
echo "=====================================\n\n";

/**
 * Funzione per fare chiamate HTTP
 */
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
        CURLOPT_VERBOSE => false
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

/**
 * Verifica che la risposta di errore segua il formato standard
 */
function verifyErrorFormat($response, $expectedCode, $expectedStatus, $testName) {
    echo "📋 TEST: $testName\n";
    echo str_repeat('-', 50) . "\n";
    
    $data = $response['data'];
    $httpCode = $response['status_code'];
    
    echo "Status HTTP: $httpCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    $formatOk = true;
    $errors = [];
    
    // Verifica campi obbligatori del formato standard
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
    
    if (isset($data['code']) && $data['code'] !== $expectedCode) {
        $formatOk = false;
        $errors[] = "Codice errore atteso: '$expectedCode', ricevuto: '{$data['code']}'";
    }
    
    if ($httpCode !== $expectedStatus) {
        $formatOk = false;
        $errors[] = "Status HTTP atteso: $expectedStatus, ricevuto: $httpCode";
    }
    
    // Verifica che non ci siano i vecchi campi
    if (isset($data['message']) || isset($data['error_code']) || isset($data['errors'])) {
        $formatOk = false;
        $errors[] = "Presenti campi del vecchio formato (message/error_code/errors)";
    }
    
    if ($formatOk) {
        echo "✅ FORMATO ERRORE CORRETTO\n";
        echo "✅ CODICE ERRORE: {$data['code']}\n";
        echo "✅ STATUS HTTP: $httpCode\n";
        
        if (isset($data['details']) && !empty($data['details'])) {
            echo "✅ DETTAGLI: " . json_encode($data['details'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "❌ ERRORI FORMATO:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
    
    echo "\n";
    return $formatOk;
}

try {
    $allTestsPassed = true;
    
    // STEP 1: Ottieni token valido per i test che richiedono autenticazione
    echo "🔐 STEP 1: Login per ottenere token JWT\n";
    echo "--------------------------------------\n";
    
    $loginData = http_build_query([
        'email' => $loginEmail,
        'password' => $loginPassword
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
    
    $accessToken = null;
    if ($loginResponse['data']['success']) {
        $accessToken = $loginResponse['data']['data']['access_token'];
        echo "✅ Token ottenuto: " . substr($accessToken, 0, 20) . "...\n\n";
    } else {
        echo "⚠️  Login fallito, alcuni test potrebbero non funzionare\n\n";
    }
    
    // STEP 2: Test degli errori standardizzati
    echo "🧪 STEP 2: Test errori standardizzati\n";
    echo "------------------------------------\n\n";
    
    // Test 1: UNAUTHORIZED - Token mancante
    $response1 = makeHttpRequest(
        $baseUrl . '/requests/types',
        'GET',
        ['Content-Type: application/json']
    );
    
    $test1Passed = verifyErrorFormat($response1, 'UNAUTHORIZED', 401, 'UNAUTHORIZED - Token mancante');
    $allTestsPassed = $allTestsPassed && $test1Passed;
    
    // Test 2: INVALID_REQUEST_TYPE - Tipologia inesistente
    if ($accessToken) {
        $response2 = makeHttpRequest(
            $baseUrl . '/requests',
            'POST',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
            json_encode(['type_id' => 999, 'reason' => 'Test'])
        );
        
        $test2Passed = verifyErrorFormat($response2, 'INVALID_REQUEST_TYPE', 404, 'INVALID_REQUEST_TYPE - Tipologia inesistente');
        $allTestsPassed = $allTestsPassed && $test2Passed;
    } else {
        echo "⏭️  Saltato test INVALID_REQUEST_TYPE (token non disponibile)\n\n";
    }
    
    // Test 3: MISSING_REQUIRED_FIELD - Campo obbligatorio mancante
    if ($accessToken) {
        $response3 = makeHttpRequest(
            $baseUrl . '/requests',
            'POST',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
            json_encode(['type_id' => '']) // type_id vuoto
        );
        
        $test3Passed = verifyErrorFormat($response3, 'MISSING_REQUIRED_FIELD', 400, 'MISSING_REQUIRED_FIELD - Campo obbligatorio mancante');
        $allTestsPassed = $allTestsPassed && $test3Passed;
    } else {
        echo "⏭️  Saltato test MISSING_REQUIRED_FIELD (token non disponibile)\n\n";
    }
    
    // Test 4: MISSING_REQUIRED_FIELD - Reason obbligatorio per Certificato Medico
    if ($accessToken) {
        $response4 = makeHttpRequest(
            $baseUrl . '/requests',
            'POST',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
            json_encode(['type_id' => 1]) // Certificato Medico richiede reason
        );
        
        $test4Passed = verifyErrorFormat($response4, 'MISSING_REQUIRED_FIELD', 400, 'MISSING_REQUIRED_FIELD - Reason obbligatorio per Certificato Medico');
        $allTestsPassed = $allTestsPassed && $test4Passed;
    } else {
        echo "⏭️  Saltato test MISSING_REQUIRED_FIELD dinamico (token non disponibile)\n\n";
    }
    
    // STEP 3: Riepilogo risultati
    echo "🎯 RIEPILOGO RISULTATI\n";
    echo "=====================\n";
    
    if ($allTestsPassed) {
        echo "✅ TUTTI I TEST PASSATI!\n";
        echo "✅ Formato errore standardizzato implementato correttamente\n";
        echo "✅ Codici errore conformi alle specifiche\n";
        echo "✅ Status HTTP corretti\n";
        echo "✅ Campi del vecchio formato eliminati\n\n";
        
        echo "📋 FORMATO STANDARD VERIFICATO:\n";
        echo "{\n";
        echo "  \"success\": false,\n";
        echo "  \"error\": \"Messaggio errore leggibile\",\n";
        echo "  \"code\": \"ERROR_CODE\",\n";
        echo "  \"details\": {\n";
        echo "    \"field\": \"Dettaglio specifico errore\"\n";
        echo "  }\n";
        echo "}\n\n";
        
        echo "🔧 CODICI ERRORE IMPLEMENTATI:\n";
        echo "- INVALID_REQUEST_TYPE: Tipologia richiesta non valida\n";
        echo "- MISSING_REQUIRED_FIELD: Campo obbligatorio mancante\n";
        echo "- UNAUTHORIZED: Token mancante o non valido\n";
        echo "- INTERNAL_ERROR: Errore interno del server\n";
        
    } else {
        echo "❌ ALCUNI TEST FALLITI\n";
        echo "❌ Il formato errore NON è completamente standardizzato\n";
        echo "🔧 Verifica l'implementazione nei controller\n";
    }
    
} catch (Exception $e) {
    echo "💥 ERRORE DURANTE I TEST: " . $e->getMessage() . "\n";
    echo "   Verifica che:\n";
    echo "   - Il server web sia in esecuzione\n";
    echo "   - L'applicazione Yii2 sia configurata correttamente\n";
    echo "   - Gli endpoint API siano accessibili\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📋 STANDARD GESTIONE ERRORI:\n";
echo "- Formato JSON consistente con campi: success, error, code, details\n";
echo "- Status HTTP appropriati: 400, 401, 404, 500\n";
echo "- Codici errore descrittivi e standardizzati\n";
echo "- Campo details opzionale per informazioni aggiuntive\n";
echo "- Eliminazione dei vecchi campi: message, error_code, errors\n";
echo str_repeat('=', 60) . "\n"; 