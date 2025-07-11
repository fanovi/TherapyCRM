<?php
// Script per controllare i dati esistenti nel database
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/frontend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/frontend/config/main.php'
);

$application = new yii\web\Application($config);

use common\models\PlanTherapy;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\Patient;

echo "=== CONTROLLO DATI ESISTENTI ===\n\n";

// Controlla PlanTherapy
echo "1. PLAN THERAPIES:\n";
$planTherapies = PlanTherapy::find()->with('therapeuticPlan')->limit(5)->all();
foreach ($planTherapies as $pt) {
    echo "ID: {$pt->id}, TherapeuticPlan ID: {$pt->therapeutic_plan_id}\n";
    if ($pt->therapeuticPlan) {
        echo "   Start: {$pt->therapeuticPlan->start_date}, End: {$pt->therapeuticPlan->end_date}\n";
        echo "   Patient ID: {$pt->therapeuticPlan->patient_id}\n";
    }
    echo "\n";
}

// Controlla Therapists
echo "2. THERAPISTS:\n";
$therapists = Therapist::find()->where(['is_active' => 1])->limit(5)->all();
foreach ($therapists as $therapist) {
    echo "ID: {$therapist->id}, Active: {$therapist->is_active}\n";
    if ($therapist->user && $therapist->user->profile) {
        echo "   Nome: {$therapist->user->profile->first_name} {$therapist->user->profile->last_name}\n";
    }
    echo "\n";
}

// Controlla TherapeuticPlans
echo "3. THERAPEUTIC PLANS:\n";
$therapeuticPlans = TherapeuticPlan::find()->limit(5)->all();
foreach ($therapeuticPlans as $tp) {
    echo "ID: {$tp->id}, Patient ID: {$tp->patient_id}\n";
    echo "   Start: {$tp->start_date}, End: {$tp->end_date}\n";
    echo "   Status: {$tp->status}\n";
    echo "\n";
}

// Suggerisci date valide per il test
echo "4. SUGGERIMENTI PER TEST:\n";
$validPlan = PlanTherapy::find()->with('therapeuticPlan')->one();
if ($validPlan && $validPlan->therapeuticPlan) {
    $startDate = new DateTime($validPlan->therapeuticPlan->start_date);
    $endDate = new DateTime($validPlan->therapeuticPlan->end_date);
    
    // Suggerisci una data di inizio una settimana dopo l'inizio del piano
    $suggestedStart = clone $startDate;
    $suggestedStart->modify('+1 week');
    
    // Suggerisci una data di fine un mese dopo la data di inizio suggerita
    $suggestedEnd = clone $suggestedStart;
    $suggestedEnd->modify('+1 month');
    
    // Assicurati che la data di fine non superi la fine del piano
    if ($suggestedEnd > $endDate) {
        $suggestedEnd = clone $endDate;
    }
    
    echo "Piano terapeutico valido trovato:\n";
    echo "   PlanTherapy ID: {$validPlan->id}\n";
    echo "   Periodo piano: {$validPlan->therapeuticPlan->start_date} - {$validPlan->therapeuticPlan->end_date}\n";
    echo "   Date suggerite per test:\n";
    echo "     validFrom: {$suggestedStart->format('Y-m-d')}\n";
    echo "     validTo: {$suggestedEnd->format('Y-m-d')}\n";
}

$validTherapist = Therapist::find()->where(['is_active' => 1])->one();
if ($validTherapist) {
    echo "   Therapist ID valido: {$validTherapist->id}\n";
}
?> 