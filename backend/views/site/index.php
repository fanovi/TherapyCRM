<?php

/** @var yii\web\View $this */
/** @var array $actionStats */

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\models\User;
use common\models\DocumentRequest;
use common\models\Patient;
use common\models\Therapist;

$this->title = 'Dashboard';

// Statistiche generali
$totalUsers = User::find()->count();
$totalPatients = Patient::find()->count();
$totalTherapists = Therapist::find()->count();
$totalRequests = DocumentRequest::find()->count();

// Statistiche di oggi
$today = date('Y-m-d 00:00:00');
$todayLogs = ActivityLog::find()
    ->where(['>=', 'created_at', $today])
    ->count();
$todayRequests = DocumentRequest::find()
    ->where(['>=', 'created_at', $today])
    ->count();

// Log recenti
$recentLogs = ActivityLog::find()
    ->with(['user'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(5)
    ->all();

// Richieste recenti
$recentRequests = DocumentRequest::find()
    ->with(['patient', 'requestType'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(5)
    ->all();

// Helper function per determinare le classi CSS dello stato
function getStatusClasses($status) {
    switch ($status) {
        case DocumentRequest::STATUS_INVIATA:
            return 'bg-yellow-100 text-yellow-800';
        case DocumentRequest::STATUS_PRESA_IN_CARICO:
            return 'bg-blue-100 text-blue-800';
        case DocumentRequest::STATUS_STAMPATO:
            return 'bg-purple-100 text-purple-800';
        case DocumentRequest::STATUS_CONSEGNATO:
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Helper function per ottenere l'etichetta dell'azione
function getActionLabel($action) {
    $labels = [
        ActivityLog::ACTION_CREATE => 'Creazione',
        ActivityLog::ACTION_UPDATE => 'Modifica',
        ActivityLog::ACTION_DELETE => 'Eliminazione'
    ];
    return $labels[$action] ?? ucfirst($action);
}
?>

<div class="site-index">
    <!-- Statistiche Generali -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Utenti Totali</p>
                    <p class="text-2xl font-semibold text-gray-700"><?= $totalUsers ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Pazienti</p>
                    <p class="text-2xl font-semibold text-gray-700"><?= $totalPatients ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Terapisti</p>
                    <p class="text-2xl font-semibold text-gray-700"><?= $totalTherapists ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Richieste</p>
                    <p class="text-2xl font-semibold text-gray-700"><?= $totalRequests ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiche Oggi e Azioni -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Statistiche di Oggi -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Attività di Oggi</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-gray-500">Log Registrati</p>
                    <p class="text-xl font-semibold text-gray-700"><?= $todayLogs ?></p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-gray-500">Richieste Ricevute</p>
                    <p class="text-xl font-semibold text-gray-700"><?= $todayRequests ?></p>
                </div>
            </div>
            
            <?php if (!empty($actionStats)): ?>
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">Statistiche per Tipo di Azione</h4>
                <div class="space-y-2">
                    <?php foreach ($actionStats as $stat): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600"><?= getActionLabel($stat['action']) ?></span>
                            <span class="text-sm font-medium text-gray-700"><?= $stat['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Azioni Rapide -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Azioni Rapide</h3>
            <div class="grid grid-cols-2 gap-4">
                <!-- <?= Html::a('<div class="flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>Nuova Richiesta</div>', 
                    ['/document-request/create'], 
                    ['class' => 'flex items-center justify-center p-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors']) ?>
                
                <?= Html::a('<div class="flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>Nuovo Paziente</div>', 
                    ['/patient/create'], 
                    ['class' => 'flex items-center justify-center p-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors']) ?>
                
                <?= Html::a('<div class="flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>Nuovo Terapista</div>', 
                    ['/therapist/create'], 
                    ['class' => 'flex items-center justify-center p-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors']) ?> -->
                
                <?= Html::a('<div class="flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>Visualizza Log</div>', 
                    ['/activity-log/index'], 
                    ['class' => 'flex items-center justify-center p-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors']) ?>
            </div>
        </div>
    </div>

    <!-- Log e Richieste Recenti -->
    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
        <!-- Log Recenti -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Log Recenti</h3>
            <div class="space-y-4">
                <?php foreach ($recentLogs as $log): ?>
                    <div class="border-b pb-4 last:border-b-0 last:pb-0">
                        <div class="flex items-start">
                            <div class="flex-grow">
                                <p class="text-sm text-gray-600"><?= Html::encode($log->getSummary()) ?></p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?= Yii::$app->formatter->asRelativeTime($log->created_at) ?> 
                                    <?php if ($log->user): ?>
                                        da <?= Html::encode($log->user->username) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?= Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>', 
                                ['/activity-log/view', 'id' => $log->id], 
                                ['class' => 'text-blue-500 hover:text-blue-600']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= Html::a('Vedi tutti i log', ['/activity-log/index'], ['class' => 'inline-block mt-4 text-blue-500 hover:text-blue-600']) ?>
        </div>

        <!-- Richieste Recenti -->
        <!-- <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Richieste Recenti</h3>
            <div class="space-y-4">
                <?php foreach ($recentRequests as $request): ?>
                    <div class="border-b pb-4 last:border-b-0 last:pb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-grow">
                                <p class="text-sm font-medium text-gray-700">
                                    <?= Html::encode($request->patient->getFullName()) ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?= Html::encode($request->requestType->name) ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?= Yii::$app->formatter->asRelativeTime($request->created_at) ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?= getStatusClasses($request->status) ?>">
                                <?= Html::encode($request->getStatusLabel()) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= Html::a('Vedi tutte le richieste', ['/document-request/index'], ['class' => 'inline-block mt-4 text-blue-500 hover:text-blue-600']) ?>
        </div> -->
    </div>
</div>
