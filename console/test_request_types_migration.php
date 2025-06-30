<?php

/**
 * Script di test per la migration request_types
 * 
 * Utilizzo:
 * php console/test_request_types_migration.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../common/config/bootstrap.php';
require_once __DIR__ . '/config/bootstrap.php';

use common\models\RequestType;
use yii\helpers\VarDumper;

// Configura Yii application
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../common/config/main.php',
    require __DIR__ . '/config/main.php'
);

new yii\console\Application($config);

echo "🧪 TEST MIGRATION REQUEST_TYPES\n";
echo "=====================================\n\n";

try {
    // Test 1: Verifica struttura tabella
    echo "📋 STEP 1: Verifica struttura tabella\n";
    echo "------------------------------------\n";
    
    $schema = Yii::$app->db->getTableSchema('{{%request_types}}', true);
    if (!$schema) {
        echo "❌ ERRORE: Tabella request_types non trovata!\n";
        exit(1);
    }
    
    $expectedColumns = [
        'id', 'name', 'therapeutic_plan_rule', 'allow_multiple_requests', 
        'require_therapy_assignment', 'require_notes', 'created_at', 'updated_at'
    ];
    
    foreach ($expectedColumns as $column) {
        if (isset($schema->columns[$column])) {
            echo "✅ Colonna '$column' presente\n";
        } else {
            echo "❌ Colonna '$column' mancante!\n";
        }
    }
    
    echo "\n";
    
    // Test 2: Verifica dati inseriti
    echo "📊 STEP 2: Verifica dati inseriti\n";
    echo "--------------------------------\n";
    
    $requestTypes = RequestType::find()->orderBy('id')->all();
    
    if (empty($requestTypes)) {
        echo "❌ ERRORE: Nessun dato trovato nella tabella!\n";
        exit(1);
    }
    
    echo "Trovati " . count($requestTypes) . " tipi di richiesta:\n\n";
    
    foreach ($requestTypes as $type) {
        echo "ID: {$type->id}\n";
        echo "Nome: {$type->name}\n";
        echo "Regola Piano Terapeutico: {$type->therapeutic_plan_rule} ({$type->getTherapeuticPlanRuleLabel()})\n";
        echo "Richieste Multiple: " . ($type->allowsMultipleRequests() ? 'Sì' : 'No') . "\n";
        echo "Richiede Terapia: " . ($type->requiresTherapyAssignment() ? 'Sì' : 'No') . "\n";
        echo "Richiede Note: " . ($type->requiresNotes() ? 'Sì' : 'No') . "\n";
        echo "---\n";
    }
    
    // Test 3: Verifica metodi del modello
    echo "\n🔧 STEP 3: Test metodi del modello\n";
    echo "--------------------------------\n";
    
    $testType = RequestType::findById(1); // Copia Piano Terapeutico
    if ($testType) {
        echo "Test su '{$testType->name}':\n";
        echo "- È piano obbligatorio? " . ($testType->isTherapeuticPlanRequired() ? 'Sì' : 'No') . "\n";
        echo "- È piano opzionale? " . ($testType->isTherapeuticPlanOptional() ? 'Sì' : 'No') . "\n";
        echo "- Piano non associabile? " . ($testType->isTherapeuticPlanNotAllowed() ? 'Sì' : 'No') . "\n";
        echo "- Permette richieste multiple? " . ($testType->allowsMultipleRequests() ? 'Sì' : 'No') . "\n";
        echo "- Richiede note? " . ($testType->requiresNotes() ? 'Sì' : 'No') . "\n";
    }
    
    // Test 4: Test API response
    echo "\n🌐 STEP 4: Test response API\n";
    echo "---------------------------\n";
    
    $apiData = RequestType::getForApi();
    echo "Dati per API (" . count($apiData) . " elementi):\n";
    
    if (!empty($apiData)) {
        $firstItem = $apiData[0];
        echo "Struttura primo elemento:\n";
        foreach ($firstItem as $key => $value) {
            $type = gettype($value);
            echo "- $key: $value ($type)\n";
        }
    }
    
    // Test 5: Test Query Builder
    echo "\n🔍 STEP 5: Test Query Builder\n";
    echo "----------------------------\n";
    
    $requiredTypes = RequestType::find()->requiresTherapeuticPlan()->all();
    echo "Tipi che richiedono piano obbligatorio: " . count($requiredTypes) . "\n";
    
    $multipleTypes = RequestType::find()->allowsMultiple()->all();
    echo "Tipi che permettono richieste multiple: " . count($multipleTypes) . "\n";
    
    $notesRequired = RequestType::find()->requiresNotes()->all();
    echo "Tipi che richiedono note: " . count($notesRequired) . "\n";
    
    // Test 6: Test dropdown options
    echo "\n📋 STEP 6: Test dropdown options\n";
    echo "--------------------------------\n";
    
    $dropdownOptions = RequestType::getDropdownOptions();
    echo "Opzioni dropdown:\n";
    foreach ($dropdownOptions as $id => $name) {
        echo "- ID $id: $name\n";
    }
    
    echo "\n✅ TUTTI I TEST COMPLETATI CON SUCCESSO!\n";
    echo "=======================================\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE DURANTE I TEST:\n";
    echo $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 