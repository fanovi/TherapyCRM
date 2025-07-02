<?php
// Debug endpoint comunicazioni
echo "🔧 DEBUG ENDPOINT COMUNICAZIONI\n";
echo "================================\n\n";

// Test URL generation
require_once(__DIR__ . '/../../common/config/bootstrap.php');
require_once(__DIR__ . '/../config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../config/main.php')
);

$application = new yii\web\Application($config);

// Test URL generation
echo "📍 URL GENERATION TEST:\n";
echo "api-mark-read: " . \yii\helpers\Url::to(['communication/api-mark-read']) . "\n";
echo "api-stats: " . \yii\helpers\Url::to(['communication/api-stats']) . "\n\n";

// Test controller method exists
echo "🎯 CONTROLLER METHODS:\n";
$controller = new \frontend\controllers\CommunicationController('communication', $application);
echo "actionApiMarkRead exists: " . (method_exists($controller, 'actionApiMarkRead') ? '✅ YES' : '❌ NO') . "\n";
echo "actionApiStats exists: " . (method_exists($controller, 'actionApiStats') ? '✅ YES' : '❌ NO') . "\n\n";

// Test routes
echo "🛣️ ROUTE TESTING:\n";
echo "Direct test URLs:\n";
echo "- http://localhost/TherapyCRM/frontend/web/index.php?r=communication/api-mark-read\n";
echo "- http://localhost/TherapyCRM/frontend/web/index.php?r=communication/api-stats\n\n";

echo "✅ Debug completed. Check URLs above.\n";
?> 