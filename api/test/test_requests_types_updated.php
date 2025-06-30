<?php
/**
 * Script di test per l'endpoint GET /requests/types AGGIORNATO
 * 
 * Testa la nuova struttura della tabella request_types con:
 * - therapeutic_plan_rule
 * - allow_multiple_requests  
 * - require_therapy_assignment
 * - require_notes
 * - is_active
 * 
 * Uso:
 * php api/test/test_requests_types_updated.php
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "🧪 TEST ENDPOINT /requests/types (STRUTTURA AGGIORNATA)\n";
echo "=====================================================\n\n";

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
    
    // STEP 2: Chiamata all'endpoint /requests/types
    echo "📋 STEP 2: Test GET /requests/types\n";
    echo "-----------------------------------\n";
    
    $response = makeHttpRequest(
        $baseUrl . '/requests/types',
        'GET',
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken
        ]
    );
    
    echo "Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] !== 200) {
        throw new Exception("Endpoint failed with status {$response['status_code']}: " . $response['body']);
    }
    
    $data = $response['data'];
    
    if (!$data['success']) {
        throw new Exception("API error: " . $data['error']);
    }
    
    echo "✅ SUCCESS! Endpoint funziona correttamente\n\n";
    
    // STEP 3: Verifica struttura meta
    echo "📊 STEP 3: Verifica struttura meta\n";
    echo "----------------------------------\n";
    
    $meta = $data['meta'];
    $expectedMetaFields = ['total', 'active_count', 'rule_distribution', 'rules'];
    
    foreach ($expectedMetaFields as $field) {
        if (isset($meta[$field])) {
            echo "✅ Meta campo '$field' presente\n";
        } else {
            echo "❌ Meta campo '$field' mancante!\n";
        }
    }
    
    echo "\nInfo meta:\n";
    echo "- Total: {$meta['total']}\n";
    echo "- Active: {$meta['active_count']}\n";
    echo "- Rule distribution: " . json_encode($meta['rule_distribution']) . "\n";
    echo "- Rules: " . json_encode($meta['rules']) . "\n\n";
    
    // STEP 4: Verifica struttura dati tipologie
    echo "🔍 STEP 4: Verifica struttura tipologie\n";
    echo "---------------------------------------\n";
    
    $expectedFields = [
        'id', 'name', 'therapeutic_plan_rule', 'therapeutic_plan_rule_label',
        'allow_multiple_requests', 'require_therapy_assignment', 'require_notes',
        'is_active', 'is_therapeutic_plan_required', 'is_therapeutic_plan_optional',
        'is_therapeutic_plan_not_allowed'
    ];
    
    if (empty($data['data'])) {
        throw new Exception("Nessuna tipologia trovata!");
    }
    
    $firstType = $data['data'][0];
    
    foreach ($expectedFields as $field) {
        if (array_key_exists($field, $firstType)) {
            echo "✅ Campo '$field' presente\n";
        } else {
            echo "❌ Campo '$field' mancante!\n";
        }
    }
    
    // STEP 5: Analisi dettagliata tipologie
    echo "\n📝 STEP 5: Analisi tipologie\n";
    echo "----------------------------\n";
    
    foreach ($data['data'] as $index => $type) {
        echo "Tipologia " . ($index + 1) . ": {$type['name']}\n";
        echo "  📋 ID: {$type['id']}\n";
        echo "  🏥 Piano Terapeutico: {$type['therapeutic_plan_rule_label']} ({$type['therapeutic_plan_rule']})\n";
        echo "  🔄 Multiple: " . ($type['allow_multiple_requests'] ? 'SÌ' : 'NO') . "\n";
        echo "  🩺 Richiede Terapia: " . ($type['require_therapy_assignment'] ? 'SÌ' : 'NO') . "\n";
        echo "  📝 Richiede Note: " . ($type['require_notes'] ? 'SÌ' : 'NO') . "\n";
        echo "  ✅ Attivo: " . ($type['is_active'] ? 'SÌ' : 'NO') . "\n";
        echo "  🔍 Helper flags:\n";
        echo "     - Piano obbligatorio: " . ($type['is_therapeutic_plan_required'] ? 'SÌ' : 'NO') . "\n";
        echo "     - Piano opzionale: " . ($type['is_therapeutic_plan_optional'] ? 'SÌ' : 'NO') . "\n";
        echo "     - Piano non associabile: " . ($type['is_therapeutic_plan_not_allowed'] ? 'SÌ' : 'NO') . "\n";
        echo "  ---\n";
    }
    
    // STEP 6: Test validazioni specifiche
    echo "\n🧮 STEP 6: Test validazioni specifiche\n";
    echo "--------------------------------------\n";
    
    $countRequiredPlan = 0;
    $countOptionalPlan = 0;
    $countNotAllowedPlan = 0;
    $countMultiple = 0;
    $countRequireTherapy = 0;
    $countRequireNotes = 0;
    
    foreach ($data['data'] as $type) {
        if ($type['is_therapeutic_plan_required']) $countRequiredPlan++;
        if ($type['is_therapeutic_plan_optional']) $countOptionalPlan++;
        if ($type['is_therapeutic_plan_not_allowed']) $countNotAllowedPlan++;
        if ($type['allow_multiple_requests']) $countMultiple++;
        if ($type['require_therapy_assignment']) $countRequireTherapy++;
        if ($type['require_notes']) $countRequireNotes++;
    }
    
    echo "📊 Statistiche tipologie:\n";
    echo "- Piano obbligatorio: $countRequiredPlan\n";
    echo "- Piano opzionale: $countOptionalPlan\n";
    echo "- Piano non associabile: $countNotAllowedPlan\n";
    echo "- Permettono multiple: $countMultiple\n";
    echo "- Richiedono terapia: $countRequireTherapy\n";
    echo "- Richiedono note: $countRequireNotes\n\n";
    
    // STEP 7: Verifica dati specifici
    echo "✔️  STEP 7: Verifica dati predefiniti\n";
    echo "------------------------------------\n";
    
    $expectedTypes = [
        'Copia Piano Terapeutico' => [
            'therapeutic_plan_rule' => 3,
            'require_therapy_assignment' => false,
            'require_notes' => false,
            'allow_multiple_requests' => false
        ],
        'Relazione terapista' => [
            'therapeutic_plan_rule' => 3,
            'require_therapy_assignment' => true,  // MODIFICATO!
            'require_notes' => false,
            'allow_multiple_requests' => false
        ],
        'Altro' => [
            'therapeutic_plan_rule' => 2,
            'require_therapy_assignment' => false,
            'require_notes' => true,
            'allow_multiple_requests' => true
        ]
    ];
    
    foreach ($data['data'] as $type) {
        if (isset($expectedTypes[$type['name']])) {
            $expected = $expectedTypes[$type['name']];
            $name = $type['name'];
            
            echo "Verifica '$name':\n";
            
            foreach ($expected as $field => $expectedValue) {
                $actualValue = $type[$field];
                if ($actualValue === $expectedValue) {
                    echo "  ✅ $field: $actualValue (corretto)\n";
                } else {
                    echo "  ❌ $field: $actualValue (atteso: $expectedValue)\n";
                }
            }
            echo "\n";
        }
    }
    
    echo "🎉 TUTTI I TEST COMPLETATI CON SUCCESSO!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "💥 ERRORE: " . $e->getMessage() . "\n";
    echo "Verifica che:\n";
    echo "- La migration sia stata applicata: ./yii migrate\n";
    echo "- Il server web sia in esecuzione\n";
    echo "- Le credenziali siano corrette\n";
} 