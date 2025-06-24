<?php
// Test script per verificare se la correzione ActionColumn funziona
require_once 'frontend/config/bootstrap.php';
require_once 'common/config/bootstrap.php';

// Crea l'applicazione frontend
$config = yii\helpers\ArrayHelper::merge(
    require 'common/config/main.php',
    require 'frontend/config/main.php'
);

$app = new yii\web\Application($config);

try {
    echo "Test: Verifica se ActionColumn è configurata correttamente...\n";
    
    // Simula il caricamento della vista coordinators
    $viewFile = 'frontend/views/user/coordinators/index.php';
    
    if (file_exists($viewFile)) {
        $content = file_get_contents($viewFile);
        
        // Cerca il pattern problematico
        if (strpos($content, "'filter' => false,") !== false && 
            strpos($content, "'class' => 'yii\\grid\\ActionColumn'") !== false) {
            echo "❌ ERRORE: Trovato ancora 'filter' => false in ActionColumn\n";
        } else {
            echo "✅ SUCCESSO: ActionColumn configurata correttamente senza 'filter' => false\n";
        }
        
        // Verifica che ActionColumn sia presente
        if (strpos($content, "'class' => 'yii\\grid\\ActionColumn'") !== false) {
            echo "✅ ActionColumn trovata nel file\n";
        } else {
            echo "❌ ActionColumn non trovata nel file\n";
        }
        
    } else {
        echo "❌ File $viewFile non trovato\n";
    }
    
    echo "\nTest completato.\n";
    
} catch (Exception $e) {
    echo "❌ Errore durante il test: " . $e->getMessage() . "\n";
} 