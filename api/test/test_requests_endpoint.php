<?php
/**
 * Script di test per l'endpoint GET /requests/types
 * 
 * Questo script dimostra come:
 * 1. Fare login per ottenere un token JWT valido
 * 2. Utilizzare il token per chiamare l'endpoint /requests/types
 * 3. Gestire le risposte dell'API
 * 
 * Uso:
 * php test_requests_endpoint.php
 */

// Configurazione
$baseUrl = 'http://localhost/TherapyCRM/api';
$loginEmail = 'paziente@test.it'; // Credenziali di test dall'AuthController
$loginPassword = '12345678';

echo "🧪 TEST ENDPOINT /requests/types\n";
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

try {
    // STEP 1: Login per ottenere token JWT
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
    
    echo "📤 Login request:\n";
    echo "   Email: $loginEmail\n";
    echo "   Password: $loginPassword\n\n";
    
    echo "📥 Login response:\n";
    echo "   Status: {$loginResponse['status_code']}\n";
    echo "   Success: " . ($loginResponse['data']['success'] ? 'TRUE' : 'FALSE') . "\n";
    
    if (!$loginResponse['data']['success']) {
        echo "❌ Login failed: " . $loginResponse['data']['message'] . "\n";
        
        // Se il login fallisce per "primo login", gestiamo il caso
        if (isset($loginResponse['data']['data']['requires_password_change']) && 
            $loginResponse['data']['data']['requires_password_change']) {
            
            echo "ℹ️  Il sistema richiede il cambio password al primo accesso.\n";
            echo "   Per questo test, simuliamo un utente che ha già cambiato la password.\n\n";
            
            // In un sistema reale, qui chiameresti /auth/change-first-password
            // Per il test, assumiamo che l'utente abbia un token valido
            $accessToken = 'demo_token_for_testing';
            echo "🔑 Usando token demo per il test: $accessToken\n\n";
        } else {
            throw new Exception("Login failed: " . $loginResponse['data']['message']);
        }
    } else {
        $accessToken = $loginResponse['data']['data']['access_token'];
        echo "🔑 Access token ottenuto: " . substr($accessToken, 0, 20) . "...\n\n";
    }
    
    // STEP 2: Chiamata all'endpoint /requests/types
    echo "📋 STEP 2: Chiamata a GET /requests/types\n";
    echo "----------------------------------------\n";
    
    $requestsResponse = makeHttpRequest(
        $baseUrl . '/requests/types',
        'GET',
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken
        ]
    );
    
    echo "📤 Request headers:\n";
    echo "   Authorization: Bearer " . substr($accessToken, 0, 20) . "...\n";
    echo "   Content-Type: application/json\n\n";
    
    echo "📥 API Response:\n";
    echo "   Status: {$requestsResponse['status_code']}\n";
    
    if ($requestsResponse['status_code'] === 200) {
        echo "✅ SUCCESS! Endpoint funziona correttamente\n\n";
        
        $data = $requestsResponse['data'];
        echo "📊 Dati ricevuti:\n";
        echo "   Success: " . ($data['success'] ? 'TRUE' : 'FALSE') . "\n";
        echo "   Total types: " . $data['meta']['total'] . "\n";
        echo "   Categories: " . implode(', ', $data['meta']['categories']) . "\n\n";
        
        echo "📝 Tipologie di richieste disponibili:\n";
        echo "   " . str_repeat('-', 50) . "\n";
        
        foreach ($data['data'] as $index => $type) {
            echo "   " . ($index + 1) . ". {$type['name']} [{$type['category']}]\n";
            echo "      📄 {$type['description']}\n";
            echo "      ⏱️  Tempo stimato: {$type['estimated_days']} giorni\n";
            echo "      🔘 Motivo richiesto: " . ($type['requires_reason'] ? 'SÌ' : 'NO') . "\n";
            echo "      📅 Date richieste: " . ($type['requires_date_range'] ? 'SÌ' : 'NO') . "\n";
            echo "      ✅ Attivo: " . ($type['is_active'] ? 'SÌ' : 'NO') . "\n\n";
        }
        
        echo "🎉 Test completato con successo!\n";
        
    } else {
        echo "❌ ERRORE nella chiamata API\n";
        echo "   Status Code: {$requestsResponse['status_code']}\n";
        echo "   Response: " . $requestsResponse['body'] . "\n";
        
        if (isset($requestsResponse['data']['message'])) {
            echo "   Message: " . $requestsResponse['data']['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "💥 ERRORE: " . $e->getMessage() . "\n";
    echo "   Verifica che:\n";
    echo "   - Il server web sia in esecuzione\n";
    echo "   - L'applicazione Yii2 sia configurata correttamente\n";
    echo "   - Le credenziali di test siano valide\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📋 RIEPILOGO ENDPOINT:\n";
echo "URL: GET $baseUrl/requests/types\n";
echo "Headers: Authorization: Bearer {jwt_token}\n";
echo "Response: JSON con tipologie di richieste\n";
echo str_repeat('=', 50) . "\n"; 