<?php

// Test per l'endpoint /requests/types

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../common/config/bootstrap.php');
require_once(__DIR__ . '/config/bootstrap.php');

use common\models\Patient;
use common\models\RequestType;
use api\controllers\RequestsController;

echo "=== TEST ENDPOINT /requests/types ===\n";

// Simula ambiente Yii
$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../common/config/main.php'),
    require(__DIR__ . '/config/main.php')
);

(new yii\web\Application($config));

try {
    // Test 1: Verifica pazienti disponibili
    echo "\n1. Cercando pazienti nel database...\n";
    $patients = Patient::find()->limit(5)->all();
    
    if (empty($patients)) {
        echo "❌ Nessun paziente trovato nel database\n";
        exit(1);
    }
    
    echo "✅ Trovati " . count($patients) . " pazienti:\n";
    foreach ($patients as $patient) {
        echo "   - ID: {$patient->id}, Nome: {$patient->first_name} {$patient->last_name}\n";
    }
    
    // Test 2: Verifica tipologie richieste
    echo "\n2. Verificando tipologie di richieste...\n";
    $requestTypes = RequestType::getForApi();
    
    if (empty($requestTypes)) {
        echo "❌ Nessuna tipologia di richiesta trovata\n";
        exit(1);
    }
    
    echo "✅ Trovate " . count($requestTypes) . " tipologie:\n";
    foreach ($requestTypes as $type) {
        echo "   - ID: {$type['id']}, Nome: {$type['name']}\n";
    }
    
    // Test 3: Testa piano terapeutico attivo per primo paziente
    echo "\n3. Testando piano terapeutico per primo paziente (ID: {$patients[0]->id})...\n";
    $activePlan = $patients[0]->getActiveTherapeuticPlan();
    
    if ($activePlan) {
        echo "✅ Piano terapeutico attivo trovato:\n";
        echo "   - ID: {$activePlan->id}\n";
        echo "   - Start: {$activePlan->start_date}\n";
        echo "   - Duration: {$activePlan->duration_days} giorni\n";
        
        // Test end_date
        try {
            $endDate = $activePlan->end_date;
            echo "   - End (diretta): {$endDate}\n";
        } catch (Exception $e) {
            echo "   - End (diretta): ERRORE - {$e->getMessage()}\n";
            try {
                $endDate = $activePlan->getCalculatedEndDate();
                echo "   - End (calcolata): {$endDate}\n";
            } catch (Exception $e2) {
                echo "   - End (calcolata): ERRORE - {$e2->getMessage()}\n";
            }
        }
    } else {
        echo "⚠️ Nessun piano terapeutico attivo per il paziente\n";
    }
    
    echo "\n=== TUTTI I TEST COMPLETATI ===\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 