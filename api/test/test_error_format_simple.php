<?php

require_once '../config/bootstrap.php';

echo "🧪 TEST FORMATO ERRORI (SEMPLIFICATO)\n";
echo "====================================\n\n";

$ch = curl_init();

// Test 1: UNAUTHORIZED - senza token
echo "📋 TEST 1: UNAUTHORIZED - senza token\n";
echo str_repeat('-', 40) . "\n";

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/TherapyCRM/api/requests/types',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);

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

if ($data['code'] !== 'UNAUTHORIZED') {
    $formatOk = false;
    $errors[] = "Codice errore atteso: 'UNAUTHORIZED', ricevuto: '{$data['code']}'";
}

if ($httpCode !== 401) {
    $formatOk = false;
    $errors[] = "Status HTTP atteso: 401, ricevuto: $httpCode";
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

curl_close($ch);

echo "🎯 RISULTATO TEST\n";
echo "================\n";
if ($formatOk) {
    echo "✅ Formato errore standardizzato implementato correttamente!\n";
    echo "   - success: false\n";
    echo "   - error: messaggio leggibile\n";
    echo "   - code: codice errore standard\n";
    echo "   - details: (opzionale) dettagli aggiuntivi\n";
} else {
    echo "❌ Formato errore NON conforme allo standard\n";
} 